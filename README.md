# Nova CMS v2

Nova CMS is an open-source **showcase + publishing + community platform** for developers and creators. It is designed for the Nova Studio developer community, but anyone can fork and self-host it.

The v2 architecture deliberately removes local SQLite/password auth from the production product.

## Stack

- **Svelte 5 + SvelteKit 2** — public website, authenticated studio, SSR
- **Tailwind CSS 4** — design system and responsive UI
- **TypeScript** — application types and safer server actions
- **Supabase Auth** — email/password authentication and email verification
- **Supabase PostgreSQL** — profiles, showcases, posts, discussions, replies and moderation reports
- **Supabase Storage** — avatars and covers
- **Supabase RLS** — authorization at database level
- **PHP 8.5 API** — complementary server-side endpoints such as health and moderation proxying
- **IndexedDB** — local editor draft autosave only; never the production source of truth
- **Vercel** — SvelteKit + PHP serverless deployment

## Product

Every member can create an account and gets a personal showcase at `/u/<slug>` with projects, articles, notes, biography and visual identity. The `/community` area is a small discussion space for Nova Studio members. The private `/studio` area manages content and identity.

## Local setup

```powershell
cd "$HOME\Desktop"
git clone https://github.com/ephraimlifanjo/cms.git
cd cms
npm install
Copy-Item .env.example .env
npm run dev
```

Create a Supabase project, apply `supabase/migrations/0001_nova_cms.sql`, then fill:

```env
PUBLIC_SUPABASE_URL=https://YOUR_PROJECT.supabase.co
PUBLIC_SUPABASE_PUBLISHABLE_KEY=sb_publishable_...
PUBLIC_SITE_URL=http://localhost:5173
SUPABASE_URL=https://YOUR_PROJECT.supabase.co
SUPABASE_PUBLISHABLE_KEY=sb_publishable_...
```

## Authentication

Nova CMS uses Supabase Auth only. Passwords never pass through the PHP API and Nova CMS does not maintain a parallel password table.

For SSR email confirmation, configure the Supabase confirmation template to point to:

```text
{{ .SiteURL }}/auth/confirm?token_hash={{ .TokenHash }}&type=email
```

Set the Supabase Site URL to your deployed domain and add localhost during development.

## Database security

The migration enables Row Level Security on all user-owned tables. Public visitors can only read published showcases/posts and public community content. Authenticated users can only mutate rows they own. Moderation reports are insert-only for normal users.

## PHP API

`api/php/index.php` is intentionally small. It exposes `/api/php/health` and `/api/php/reports`. Reports are forwarded to Supabase using the caller's JWT so RLS still decides access.

## Deploy

The SvelteKit app uses `@sveltejs/adapter-vercel`; `vercel.json` additionally maps the PHP API to `vercel-php`.

Required Vercel environment variables:

```text
PUBLIC_SUPABASE_URL
PUBLIC_SUPABASE_PUBLISHABLE_KEY
PUBLIC_SITE_URL
SUPABASE_URL
SUPABASE_PUBLISHABLE_KEY
```

## Open source

MIT. Fork it, rebrand it, add community channels, or turn off the community module and use it as a personal portfolio/blog.
