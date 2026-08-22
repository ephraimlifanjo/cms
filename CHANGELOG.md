# Changelog

## 1.0.2 — 2026-08-22

- Added the local development demo account `admin / 1234` when running with `php -S`.
- Kept production admin access environment-controlled instead of exposing `1234` publicly.
- Added live article-cover preview for both image URLs and selected local image files.
- Added a polished empty-image preview state in the article editor.
- Updated documentation for image handling on local PHP and Vercel/serverless hosting.

## 1.0.0 — 2026-08-22

- Rebuilt the original educational CRUD into a usable micro-CMS.
- Replaced hard-coded MySQL credentials with environment-driven PDO storage.
- Added SQLite local development and remote MySQL/PostgreSQL support.
- Removed hard-coded `admin/1234` authentication.
- Added CSRF protection, login throttling and secure sessions.
- Removed SQL injection paths from edit/delete operations.
- Changed article deletion from GET to POST with confirmation.
- Added safe image validation and randomized upload filenames.
- Added draft/published workflow, categories, tags, featured articles and SEO fields.
- Added responsive public blog, search, article pages and admin dashboard.
- Added Vercel community-PHP runtime configuration.
- Added README, security policy and production environment template.
