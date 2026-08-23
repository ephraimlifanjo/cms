# Nova CMS — Showcase-first architecture

Nova CMS v1.2 treats the public website as the product and the CMS as invisible infrastructure.

## UX rule

A visitor should never land on a directory of creators or a CMS marketing homepage. The root `/` is the owner's actual website.

## Public information architecture

1. Hero / identity
2. Selected work
3. Journal
4. About
5. Individual article pages
6. Small footer attribution

The layout is intentionally editorial rather than dashboard-like. Images are large, typography carries the hierarchy and content blocks have generous whitespace.

## Admin information architecture

The admin is private and task-oriented:

- overview;
- create/edit articles;
- drafts vs published;
- featured work;
- image selection/upload;
- public identity and appearance;
- account/password.

The admin should not leak into the public navigation beyond a small optional `Admin` entry.

## Content model

The existing `articles` table supplies both work cards and journal entries.

- `featured = 1` means the item is eligible for **Selected work**.
- all published items can appear in **Journal**.
- `category` can be used as a project/article label.
- `image` is the lead visual.
- `excerpt` is the card summary.
- `content` is the long-form case study/article body.

This keeps the CMS small while allowing the same content engine to serve portfolio projects, case studies, tutorials, notes or announcements.

## Selecting the public site

Set:

```env
CMS_PUBLIC_SITE_SLUG=my-site
```

The matching site is rendered directly on `/`. If no slug is configured, the first available site is used.

## Why the multi-user tables still exist

`users` and `sites` remain in the core because they are useful extension points for developers who want to turn Nova CMS into a hosted multi-site service. They are no longer the default public UX.

The default product remains: **one person, one showcase, one private admin.**
