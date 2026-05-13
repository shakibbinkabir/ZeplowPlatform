import type { Metadata } from 'next';
import './globals.css';

// PHASE 1 STUB — minimal layout so `pnpm dev:narrative` boots on port 3001.
// Phase 2 will replace this with the real layout: localFont (Playfair +
// Manrope), Navigation, Footer, getSiteConfig('narrative') data fetch.

export const metadata: Metadata = {
  metadataBase: new URL('https://narrative.zeplow.com'),
  title: 'Zeplow Narrative — Stories that sell.',
  description:
    'Brand storytelling, identity & content systems. We turn businesses into stories worth following.',
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en">
      <body className="bg-background text-text font-body antialiased">
        {children}
      </body>
    </html>
  );
}
