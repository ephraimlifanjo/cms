# Security policy

Nova CMS v1.1 removes the shared static-admin model. Accounts use per-user password hashes and signed authentication cookies.

## Production requirements

1. Set a unique `APP_KEY` of at least 32 characters.
2. Use HTTPS.
3. On serverless hosting, use a persistent PostgreSQL/MySQL database.
4. Never commit `.env`, database URLs, passwords or provider credentials.
5. Rotate a secret immediately if it was ever published.

## Authentication

- passwords use `password_hash(PASSWORD_DEFAULT)`;
- login uses `password_verify()`;
- the auth cookie payload is HMAC signed with `APP_KEY`;
- password changes require the current password;
- users can mutate only the site/articles that belong to their account.

## Requests

State-changing forms require a CSRF token. Destructive operations use POST. Database reads/writes use prepared statements for user-provided values.

## Uploads

Classic-hosting uploads are limited to 5 MB and validated by MIME type. Vercel does not provide persistent local uploads, so use built-in SVGs, external image URLs or object storage.

Report vulnerabilities privately to the repository owner rather than publishing exploit details in a public issue.
