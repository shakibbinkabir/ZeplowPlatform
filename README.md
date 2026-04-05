# Zeplow Platform

Backend applications (CMS + API) for the Zeplow website platform.

## Directory Structure

```
Platform/
├── cms/          — Laravel 11 + Filament v3 admin panel (content management)
├── api/          — Laravel 11 API (content serving) — coming in Phase 3
└── *.md          — Product Requirements Documents
```

The frontend sites live in a separate repository (`zeplow-sites`).

## Tech Stack

- **PHP 8.2+** with Laravel 11
- **Filament v3** for the admin panel
- **Spatie MediaLibrary** for image/media management
- **MySQL** for data storage
- **cPanel** hosting (production)
