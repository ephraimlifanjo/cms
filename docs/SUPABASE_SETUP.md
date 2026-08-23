# Supabase setup

1. Create one dedicated Supabase project for Nova CMS.
2. Run `supabase/migrations/0001_nova_cms.sql`.
3. In Auth URL configuration, set the production Site URL and add `http://localhost:5173/**` as a development redirect.
4. Configure the Confirm signup email template to use `{{ .SiteURL }}/auth/confirm?token_hash={{ .TokenHash }}&type=email`.
5. Copy the Project URL and a publishable key into the SvelteKit/Vercel environment variables.
6. Keep service-role keys server-only. The v2 baseline does not require a service-role key for normal user flows.
7. After applying migrations, run Supabase security and performance advisors and resolve any warning before production.

The migration creates the user profile/showcase automatically after a Supabase Auth signup, so the frontend does not need to manufacture identity rows itself.
