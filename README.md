# Nova CMS

Nova CMS is a small open-source PHP publishing CMS for blogs, portfolios, associations and small teams. It stays intentionally simple: no framework, no Composer dependency, and a clean admin dashboard.

## Features

- Public responsive blog with search, categories and featured posts
- Draft/published workflow
- Admin dashboard and article editor
- SEO title and meta description per article
- Local image uploads with MIME/size validation
- External image URLs for serverless deployments
- Live cover-image preview in the editor
- PDO with SQLite locally and MySQL/PostgreSQL through `DATABASE_URL`
- CSRF protection, prepared statements, session hardening and login throttling
- POST-only destructive actions
- Mobile-first UI

## Requirements

PHP 8.2+ with PDO and `fileinfo`.

## Run locally

```powershell
php -S localhost:8000
```

Open `http://localhost:8000`.

### Local demo admin

When Nova CMS runs through PHP's built-in development server, the demo account is:

```text
username: admin
password: 1234
```

This `1234` password is **development/demo only**. Public production deployments keep admin access disabled unless `CMS_ADMIN_PASSWORD` is explicitly configured server-side.

## Images

In the article editor you can either:

- paste an HTTPS image URL, or
- choose a JPG, PNG, WebP or GIF file locally (5 MB maximum).

The editor shows the cover immediately before saving. On Vercel/serverless hosting, use an external image URL or persistent object storage because the local filesystem is not durable.

## Production configuration

Set at least:

```env
APP_URL=https://your-domain.example
CMS_SITE_NAME=My publication
CMS_ADMIN_USERNAME=admin
CMS_ADMIN_PASSWORD=<long-random-secret>
DATABASE_URL=postgresql://user:password@host:5432/database
```

`DATABASE_URL` can also use `mysql://...`.

### Vercel

This repository includes `vercel.json` using the community `vercel-php` runtime. Vercel functions do **not** provide durable local disk storage, so production must use a persistent remote MySQL/PostgreSQL database. For images on Vercel, use external image URLs or connect an object-storage/CDN provider.

## Security

Do not commit `.env`. Never use `1234` for a public deployment. Rotate any credential that has ever been committed. See [SECURITY.md](SECURITY.md).

## License

MIT.
