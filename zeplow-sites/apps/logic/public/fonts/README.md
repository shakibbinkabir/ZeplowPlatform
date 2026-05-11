# Logic Site Fonts

Self-host the following `.woff2` files in this directory. They are referenced
by `app/layout.tsx` via `next/font/local` in Phase 2.

## Required files

| File | Source |
|:---|:---|
| `JetBrainsMono-Bold.woff2` | https://www.jetbrains.com/lp/mono/ (700 weight) |
| `Inter-Regular.woff2` | https://rsms.me/inter/ (400 weight) |
| `Inter-Medium.woff2` | https://rsms.me/inter/ (500 weight) |
| `Inter-SemiBold.woff2` | https://rsms.me/inter/ (600 weight) |
| `Inter-Bold.woff2` | https://rsms.me/inter/ (700 weight) |

Recommended conversion: use `google-webfonts-helper` (https://gwfh.mranftl.com/)
to download woff2 variants of each weight, then rename to match the filenames
above.

Do not commit these to a public repo if the license requires attribution
embedding. Both JetBrains Mono (OFL 1.1) and Inter (OFL 1.1) are fine to
self-host.
