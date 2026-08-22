# Changelog

## 1.1.0 — 2026-08-22

### Added
- real multi-user accounts with self-service registration
- `users` and `sites` database models
- per-user personal dashboard and isolated article ownership
- password hashing, login and account password change
- site identity settings: name, slug, tagline, bio, avatar, cover and accent color
- public creator directory, individual blog pages and article pages
- six SVG image assets committed to GitHub
- redesigned responsive UI for landing, auth, dashboard, editor and public blogs
- serverless read-only safety mode when no persistent Vercel database exists

### Changed
- static environment admin password is no longer the primary authentication system
- article URLs and queries are scoped by site
- README now documents local SQLite and production PostgreSQL/MySQL behavior

## 1.0.0 — 2026-08-22

- initial production hardening of the original PHP CRUD
- prepared statements, CSRF, safer uploads and Vercel runtime configuration
