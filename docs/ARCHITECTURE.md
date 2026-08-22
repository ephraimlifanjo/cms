# Nova CMS architecture

Nova CMS v1.1 is intentionally small but multi-tenant.

## Domain model

- `users`: authentication identity, display name, password hash and role.
- `sites`: one personal publishing site owned by a user.
- `articles`: content scoped to a site and author.

Every article mutation is filtered by the authenticated user's site ID. Public reads use the site's unique slug plus the article slug.

## Authentication

Passwords are never stored in plaintext. Registration uses `password_hash(PASSWORD_DEFAULT)` and login/password changes use `password_verify()`.

Authenticated sessions use a signed HttpOnly cookie. `APP_KEY` signs the cookie and CSRF token. Production must provide its own `APP_KEY`.

## Storage modes

- Local/classic PHP: SQLite is the zero-credential default.
- Production: PostgreSQL or MySQL through `DATABASE_URL`.
- Vercel without `DATABASE_URL`: public read-only demo only; account writes are disabled because `/tmp` is ephemeral.

## Media

Built-in SVG artwork lives in `assets/images`. Classic hosting can accept validated local uploads. Serverless deployments should use built-in assets, HTTPS image URLs or persistent object storage.

## Public UI

`index.php` is the creator directory and product landing page. `site.php` renders one creator site. `article.php` renders a published article.

## Private UI

`register.php` / `login.php` manage creator identity. `admin.php` is the personal dashboard. `settings.php` controls site identity. `account.php` changes the password. `add_article.php` and `edit_article.php` manage content.
