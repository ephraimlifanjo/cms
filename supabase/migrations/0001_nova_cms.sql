begin;

create extension if not exists pgcrypto;

create table if not exists public.profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  username text not null unique check (username ~ '^[a-z0-9][a-z0-9-]{2,31}$'),
  display_name text not null check (char_length(display_name) between 2 and 80),
  bio text not null default '',
  avatar_url text not null default '',
  website_url text not null default '',
  github_url text not null default '',
  role text not null default 'member' check (role in ('member','moderator','admin')),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now()
);

create table if not exists public.showcases (
  id uuid primary key default gen_random_uuid(),
  owner_id uuid not null unique,
  slug text not null unique check (slug ~ '^[a-z0-9][a-z0-9-]{2,47}$'),
  title text not null default 'My showcase',
  headline text not null default 'Building useful things and sharing the process.',
  about text not null default '',
  location text not null default '',
  cover_url text not null default '',
  accent text not null default '#635bff' check (accent ~ '^#[0-9A-Fa-f]{6}$'),
  is_published boolean not null default true,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint showcases_owner_id_fkey foreign key (owner_id) references public.profiles(id) on delete cascade
);

create table if not exists public.posts (
  id uuid primary key default gen_random_uuid(),
  showcase_id uuid not null,
  author_id uuid not null,
  kind text not null default 'article' check (kind in ('project','article','note')),
  slug text not null check (slug ~ '^[a-z0-9][a-z0-9-]{1,95}$'),
  title text not null check (char_length(title) between 2 and 180),
  excerpt text not null default '',
  content text not null default '',
  cover_url text not null default '',
  tags text[] not null default '{}',
  status text not null default 'draft' check (status in ('draft','published')),
  featured boolean not null default false,
  published_at timestamptz,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint posts_showcase_id_fkey foreign key (showcase_id) references public.showcases(id) on delete cascade,
  constraint posts_author_id_fkey foreign key (author_id) references public.profiles(id) on delete cascade,
  constraint posts_showcase_slug_key unique (showcase_id, slug)
);

create table if not exists public.community_channels (
  id text primary key,
  label text not null,
  description text not null default '',
  sort_order integer not null default 0
);

insert into public.community_channels (id,label,description,sort_order) values
  ('general','General','Talk with the Nova Studio community.',10),
  ('showcase','Showcase','Share what you are building.',20),
  ('help','Help','Ask technical questions and unblock each other.',30),
  ('opportunities','Opportunities','Jobs, collaboration and community opportunities.',40)
on conflict (id) do update set label=excluded.label, description=excluded.description, sort_order=excluded.sort_order;

create table if not exists public.threads (
  id uuid primary key default gen_random_uuid(),
  author_id uuid not null,
  channel_id text not null default 'general' references public.community_channels(id),
  title text not null check (char_length(title) between 3 and 180),
  body text not null check (char_length(body) between 3 and 12000),
  is_locked boolean not null default false,
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint threads_author_id_fkey foreign key (author_id) references public.profiles(id) on delete cascade
);

create table if not exists public.replies (
  id uuid primary key default gen_random_uuid(),
  thread_id uuid not null,
  author_id uuid not null,
  body text not null check (char_length(body) between 1 and 8000),
  created_at timestamptz not null default now(),
  updated_at timestamptz not null default now(),
  constraint replies_thread_id_fkey foreign key (thread_id) references public.threads(id) on delete cascade,
  constraint replies_author_id_fkey foreign key (author_id) references public.profiles(id) on delete cascade
);

create table if not exists public.thread_reactions (
  thread_id uuid not null references public.threads(id) on delete cascade,
  user_id uuid not null references public.profiles(id) on delete cascade,
  reaction text not null default 'like' check (reaction in ('like','useful','celebrate')),
  created_at timestamptz not null default now(),
  primary key (thread_id,user_id,reaction)
);

create table if not exists public.reports (
  id uuid primary key default gen_random_uuid(),
  reporter_id uuid not null references public.profiles(id) on delete cascade,
  resource_type text not null check (resource_type in ('thread','reply','post','profile')),
  resource_id uuid not null,
  reason text not null check (char_length(reason) between 3 and 1000),
  status text not null default 'open' check (status in ('open','reviewed','closed')),
  created_at timestamptz not null default now()
);

create index if not exists posts_public_idx on public.posts (status, featured, published_at desc);
create index if not exists posts_showcase_idx on public.posts (showcase_id, updated_at desc);
create index if not exists threads_channel_idx on public.threads (channel_id, created_at desc);
create index if not exists replies_thread_idx on public.replies (thread_id, created_at asc);

create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
  new.updated_at = now();
  return new;
end;
$$;

create or replace function public.slug_base(value text)
returns text
language sql
immutable
as $$
  select trim(both '-' from regexp_replace(lower(coalesce(value,'')), '[^a-z0-9]+', '-', 'g'));
$$;

create or replace function public.handle_new_user()
returns trigger
language plpgsql
security definer set search_path = public
as $$
declare
  base text;
  generated_username text;
  display text;
begin
  base := public.slug_base(coalesce(new.raw_user_meta_data->>'username', split_part(coalesce(new.email,'member'),'@',1)));
  if char_length(base) < 3 then base := 'member'; end if;
  generated_username := left(base,24) || '-' || substr(replace(new.id::text,'-',''),1,6);
  display := coalesce(nullif(trim(new.raw_user_meta_data->>'display_name'),''), split_part(coalesce(new.email,'Nova member'),'@',1));

  insert into public.profiles (id,username,display_name)
  values (new.id, generated_username, left(display,80));

  insert into public.showcases (owner_id,slug,title,headline)
  values (new.id, generated_username, left(display,80), 'Building useful things and sharing the process.');

  return new;
end;
$$;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
after insert on auth.users
for each row execute procedure public.handle_new_user();

drop trigger if exists profiles_updated_at on public.profiles;
create trigger profiles_updated_at before update on public.profiles for each row execute procedure public.set_updated_at();
drop trigger if exists showcases_updated_at on public.showcases;
create trigger showcases_updated_at before update on public.showcases for each row execute procedure public.set_updated_at();
drop trigger if exists posts_updated_at on public.posts;
create trigger posts_updated_at before update on public.posts for each row execute procedure public.set_updated_at();
drop trigger if exists threads_updated_at on public.threads;
create trigger threads_updated_at before update on public.threads for each row execute procedure public.set_updated_at();
drop trigger if exists replies_updated_at on public.replies;
create trigger replies_updated_at before update on public.replies for each row execute procedure public.set_updated_at();

alter table public.profiles enable row level security;
alter table public.showcases enable row level security;
alter table public.posts enable row level security;
alter table public.community_channels enable row level security;
alter table public.threads enable row level security;
alter table public.replies enable row level security;
alter table public.thread_reactions enable row level security;
alter table public.reports enable row level security;

create policy profiles_public_read on public.profiles for select using (true);
create policy profiles_owner_update on public.profiles for update using (id = auth.uid()) with check (id = auth.uid());

create policy showcases_public_read on public.showcases for select using (is_published or owner_id = auth.uid());
create policy showcases_owner_insert on public.showcases for insert with check (owner_id = auth.uid());
create policy showcases_owner_update on public.showcases for update using (owner_id = auth.uid()) with check (owner_id = auth.uid());
create policy showcases_owner_delete on public.showcases for delete using (owner_id = auth.uid());

create policy posts_public_read on public.posts for select using (status = 'published' or author_id = auth.uid());
create policy posts_owner_insert on public.posts for insert with check (
  author_id = auth.uid() and exists (select 1 from public.showcases s where s.id = showcase_id and s.owner_id = auth.uid())
);
create policy posts_owner_update on public.posts for update using (author_id = auth.uid()) with check (
  author_id = auth.uid() and exists (select 1 from public.showcases s where s.id = showcase_id and s.owner_id = auth.uid())
);
create policy posts_owner_delete on public.posts for delete using (author_id = auth.uid());

create policy community_channels_public_read on public.community_channels for select using (true);
create policy threads_public_read on public.threads for select using (true);
create policy threads_member_insert on public.threads for insert to authenticated with check (author_id = auth.uid());
create policy threads_owner_update on public.threads for update using (author_id = auth.uid()) with check (author_id = auth.uid());
create policy threads_owner_delete on public.threads for delete using (author_id = auth.uid());

create policy replies_public_read on public.replies for select using (true);
create policy replies_member_insert on public.replies for insert to authenticated with check (author_id = auth.uid());
create policy replies_owner_update on public.replies for update using (author_id = auth.uid()) with check (author_id = auth.uid());
create policy replies_owner_delete on public.replies for delete using (author_id = auth.uid());

create policy reactions_public_read on public.thread_reactions for select using (true);
create policy reactions_member_insert on public.thread_reactions for insert to authenticated with check (user_id = auth.uid());
create policy reactions_owner_delete on public.thread_reactions for delete using (user_id = auth.uid());

create policy reports_member_insert on public.reports for insert to authenticated with check (reporter_id = auth.uid());

insert into storage.buckets (id,name,public,file_size_limit,allowed_mime_types)
values
  ('avatars','avatars',true,5242880,array['image/jpeg','image/png','image/webp']),
  ('covers','covers',true,10485760,array['image/jpeg','image/png','image/webp','image/gif'])
on conflict (id) do update set public=excluded.public, file_size_limit=excluded.file_size_limit, allowed_mime_types=excluded.allowed_mime_types;

create policy media_public_read on storage.objects for select using (bucket_id in ('avatars','covers'));
create policy media_owner_insert on storage.objects for insert to authenticated with check (
  bucket_id in ('avatars','covers') and (storage.foldername(name))[1] = auth.uid()::text
);
create policy media_owner_update on storage.objects for update to authenticated using (
  bucket_id in ('avatars','covers') and (storage.foldername(name))[1] = auth.uid()::text
) with check (
  bucket_id in ('avatars','covers') and (storage.foldername(name))[1] = auth.uid()::text
);
create policy media_owner_delete on storage.objects for delete to authenticated using (
  bucket_id in ('avatars','covers') and (storage.foldername(name))[1] = auth.uid()::text
);

commit;
