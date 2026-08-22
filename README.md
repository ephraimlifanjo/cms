# Nova CMS

**Nova CMS** is an open-source multi-user PHP publishing platform for developers, creators, students and communities that want a small personal blog without adopting a large CMS framework.

Repository: **https://github.com/ephraimlifanjo/cms**  
Demo: **https://nova-cms-php.vercel.app**

## What changed in v1.1

Nova CMS is no longer a single shared `admin/password` blog. Every creator can:

- create an account with email + their own password;
- get an isolated personal site;
- choose a public site slug;
- edit profile, bio, avatar, cover and accent color;
- publish drafts or live articles;
- use built-in SVG covers stored in this repository;
- add external HTTPS images or local uploads on persistent PHP hosting;
- change their password from the account screen;
- share their public site URL.

Passwords are created by users and stored with PHP `password_hash()`. **There is no static admin password in this repository.**

## Stack

- PHP 8.2+
- PDO
- SQLite for local/shared-hosting quick start
- PostgreSQL or MySQL via `DATABASE_URL`
- Vanilla CSS + JavaScript
- no Composer dependency
- GitHub Actions CI

## Quick start on Windows

```powershell
cd "$HOME\Desktop"
git clone https://github.com/ephraimlifanjo/cms.git
cd cms
Copy-Item .env.example .env
php -S localhost:8000
```

Open `http://localhost:8000/register.php`, create your account and choose your password. Local development automatically creates a private `storage/app.key` and `storage/cms.sqlite` if you leave those values empty.

## Production environment

Set:

```env
APP_URL=https://your-domain.example
APP_KEY=<random secret with at least 32 characters>
DATABASE_URL=postgresql://user:password@host:5432/database
PLATFORM_OWNER_EMAIL=owner@example.com
```

Generate an `APP_KEY` locally:

```powershell
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

The user that registers with `PLATFORM_OWNER_EMAIL` receives the `admin` platform role. Other accounts receive the `creator` role.

## Database behavior

### Local / classic PHP hosting

If `DATABASE_URL` is empty, Nova CMS uses `storage/cms.sqlite`. This is persistent on a normal server filesystem and requires no external database credentials.

### Vercel

Vercel Functions have an ephemeral writable filesystem. Therefore SQLite under `/tmp` is **demo-only**. A real multi-user Vercel deployment requires a persistent PostgreSQL/MySQL `DATABASE_URL` plus `APP_KEY`.

Until these are configured, the public demo remains readable but registration/login are intentionally disabled rather than pretending that user data is durable.

## Images

The repository includes reusable SVG assets under `assets/images/`:

- `logo-mark.svg`
- `hero-studio.svg`
- `avatar-default.svg`
- `cover-code.svg`
- `cover-creative.svg`
- `blog-default.svg`

They work without Cloudinary, S3 or another account. On classic PHP hosting you can also upload JPG/PNG/WebP/GIF up to 5 MB. On Vercel use external HTTPS images or connect object storage for persistent uploads.

## Security

- `password_hash()` / `password_verify()`
- signed authentication cookie
- CSRF protection
- PDO prepared statements
- per-site ownership checks on article mutations
- POST-only delete/logout actions
- MIME and size validation for uploads
- secure/HttpOnly/SameSite cookies
- security response headers
- `.env`, app key and SQLite DB ignored by Git

See [SECURITY.md](SECURITY.md).

## Project structure

```text
assets/images/       built-in public artwork
api/index.php        Vercel PHP front controller
bootstrap.php        config, auth, database, migrations, helpers
register.php         creator signup
login.php            creator login
admin.php            personal dashboard
settings.php         site customization
account.php          password management
add_article.php      create content
edit_article.php     edit content
site.php             public creator site
article.php          public article page
```

## Contributing

Fork the repository, create a branch, run the CI checks locally if possible, and open a pull request. The goal is to keep Nova CMS understandable for PHP learners while still following production-grade security habits.

## License

MIT.
