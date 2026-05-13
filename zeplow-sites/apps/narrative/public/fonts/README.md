# Narrative Site Fonts

Self-hosted woff2 files referenced by `app/layout.tsx` via `next/font/local`
once Phase 2 wires up the layout.

## Files in this directory

| File | Source | Weights | Notes |
|:---|:---|:---|:---|
| `PlayfairDisplay-Bold.woff2` | Google Fonts (v40, latin subset) | 700 | Static font — only Bold needed for headings |
| `Manrope-Variable.woff2` | Google Fonts (v20, latin subset) | 400–700 (variable) | Variable font — single file serves all weights via font-variation-settings |

## Notes

- Both fonts are licensed under SIL Open Font License (OFL 1.1) — fine to self-host.
- Latin subset only (no Cyrillic / Greek / Vietnamese) — site audience is English-speaking. Add more subsets if expanding to those markets.
- The Narrative PRD originally specified 4 static Manrope files (Regular/Medium/SemiBold/Bold). Google Fonts now serves Manrope only as a variable font, so we deviate: one file, full weight range. This is modern best practice and saves ~75 KB vs. 4 static files.

## How these were obtained

```bash
# Playfair Display 700 latin
curl -A "Mozilla/5.0 ..." \
  "https://fonts.gstatic.com/s/playfairdisplay/v40/nuFvD-vYSZviVYUb_rj3ij__anPXJzDwcbmjWBN2PKeiunDXbtM.woff2" \
  -o PlayfairDisplay-Bold.woff2

# Manrope variable latin
curl -A "Mozilla/5.0 ..." \
  "https://fonts.gstatic.com/s/manrope/v20/xn7gYHE41ni1AdIRggexSg.woff2" \
  -o Manrope-Variable.woff2
```

The URLs come from `https://fonts.googleapis.com/css2?family=...` queried with a modern browser User-Agent (Google Fonts serves different URLs to older UAs).
