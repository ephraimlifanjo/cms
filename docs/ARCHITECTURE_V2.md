# Nova CMS v2 architecture

## Principle

Nova CMS v2 separates four responsibilities:

1. **Presentation** — SvelteKit renders the public showcase, community and private studio.
2. **Identity and persistence** — Supabase owns users, sessions, PostgreSQL data and media.
3. **Authorization** — PostgreSQL Row Level Security is the final security boundary.
4. **Backend services** — PHP handles complementary server-side HTTP endpoints without becoming a second authentication system.

## Request flow

```text
Browser
  |
  +-- SvelteKit SSR --------------------+
  |     |                                |
  |     +-- @supabase/ssr cookies        |
  |     +-- PostgREST / Storage --------> Supabase
  |
  +-- /api/php/* --> PHP function ------> Supabase REST
                                             |
                                             +-- Auth
                                             +-- PostgreSQL + RLS
                                             +-- Storage
                                             +-- Realtime-ready community data
```

## Modules

- `/` — product/community landing and discover surface
- `/u/[slug]` — personal showcase
- `/u/[slug]/[post]` — long-form project/article/note
- `/community` — discussions and questions
- `/auth/*` — Supabase email/password flows
- `/studio` — protected creator dashboard
- `/studio/new` — editor with IndexedDB draft recovery
- `/studio/settings` — profile/showcase identity and media
- `/api/php/*` — small PHP API

## Why IndexedDB still exists

IndexedDB is useful for a draft that has not been submitted yet, especially if the browser closes or the network disappears. It is not used for user identity, published content, permissions, community data or production persistence.

## No duplicated authentication

The application has one identity provider: Supabase Auth. SvelteKit SSR reads Supabase sessions from cookies. PHP receives a bearer JWT only when it needs to act on behalf of an authenticated user.
