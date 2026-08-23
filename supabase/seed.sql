-- Nova CMS v2 intentionally does not insert fake auth.users rows.
-- Create development users through /auth/register so Supabase Auth,
-- the profile trigger and RLS are exercised exactly as in production.

insert into public.community_channels (id,label,description,sort_order) values
  ('general','General','Talk with the Nova Studio community.',10),
  ('showcase','Showcase','Share what you are building.',20),
  ('help','Help','Ask technical questions and unblock each other.',30),
  ('opportunities','Opportunities','Jobs, collaboration and community opportunities.',40)
on conflict (id) do nothing;
