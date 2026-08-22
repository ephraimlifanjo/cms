# Security policy

## Production checklist

- Configure a unique `CMS_ADMIN_PASSWORD` of at least 16 random characters.
- Keep `.env` and database credentials outside Git.
- Use HTTPS in production.
- Use a dedicated database account with minimum required permissions.
- Back up the database regularly.
- Use persistent object storage for uploads in serverless environments.

## Built-in protections

Nova CMS uses prepared PDO statements, integer validation, CSRF tokens, POST-only destructive operations, secure session cookies, session-ID rotation after login, login throttling, MIME-checked image uploads and defensive HTTP headers.

## Reporting

Please contact the repository owner privately for security reports.
