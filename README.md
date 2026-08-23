# Nova CMS

**Nova CMS** is an open-source PHP CMS for building a personal showcase: selected work, projects, articles, notes, images and an about section — with a small private admin behind it.

Repository: **https://github.com/ephraimlifanjo/cms**  
Demo: **https://nova-cms-php.vercel.app**

## Product direction — v1.2

Nova CMS is **showcase-first, CMS-second**.

A visitor should feel that they are visiting a person's website, not a SaaS, directory, community feed or CMS marketing page.

The public root `/` now presents:

- a personal hero and visual identity;
- selected work / projects;
- a journal for articles and notes;
- large images and editorial layouts;
- an About section;
- a discreet link to the private administration;
- a very small “Powered by Nova CMS” signature in the footer.

The CMS exists behind the presentation. The owner edits the content; visitors only see the showcase.

## Architecture

```text
PUBLIC EXPERIENCE
/
├── Hero / identity
├── Selected work
├── Journal
├── About
└── Article pages

PRIVATE CMS
/login.php
└── /admin.php
    ├── articles
    ├── drafts
    ├── images
    ├── appearance
    └── account/security

CORE
bootstrap.php
├── PDO database
├── authentication
├── CSRF/security
├── migrations
└── content helpers
```

The existing users/sites engine remains available internally for developers who want to extend Nova CMS into a hosted multi-site product, but **the default public product is a single personal showcase**.

To choose which site is rendered at `/`, set:

```env
CMS_PUBLIC_SITE_SLUG=my-site-slug
```

If it is not set, Nova CMS renders the first available site.

## Stack

- PHP 8.2+
- PDO
- SQLite for local/classic hosting
- PostgreSQL or MySQL through `DATABASE_URL`
- Vanilla CSS + JavaScript
- no Composer dependency
- GitHub Actions CI

## Quick start

```powershell
cd "$HOME\Desktop"
git clone https://github.com/ephraimlifanjo/cms.git
cd cms
Copy-Item .env.example .env
php -S localhost:8000
```

Then open:

```text
http://localhost:8000
```

Administration:

```text
http://localhost:8000/login.php
```

## Production environment

```env
APP_URL=https://your-domain.example
APP_KEY=<random secret with at least 32 characters>
DATABASE_URL=postgresql://user:password@host:5432/database
CMS_PUBLIC_SITE_SLUG=my-showcase
PLATFORM_OWNER_EMAIL=owner@example.com
```

Generate an app key:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

## Images

Built-in reusable artwork lives in `assets/images/` and works without a third-party image account. The showcase accepts local assets and HTTPS image URLs. Classic PHP hosting can also persist uploaded JPG/PNG/WebP/GIF images.

## Security

- `password_hash()` / `password_verify()`
- signed authentication cookie
- CSRF protection
- PDO prepared statements
- content ownership checks
- POST-only destructive actions
- MIME/size validation for uploads
- Secure / HttpOnly / SameSite cookies
- security response headers
- secrets and database files ignored by Git

See [SECURITY.md](SECURITY.md).

## Main files

```text
index.php             public personal showcase
assets/showcase.css   showcase-specific design system
article.php           public long-form article
login.php             private admin login
admin.php             content dashboard
settings.php          identity / appearance
account.php           password/security
add_article.php       create content
edit_article.php      edit content
bootstrap.php         core, auth, DB, helpers
site.php              legacy/alternate public site renderer
```

## Fork it for your own site

Nova CMS is intentionally small. A developer can fork it, replace the demo identity, configure one public site, deploy it and use the admin to keep the showcase alive without adopting WordPress or a large framework.

## License

MIT.
