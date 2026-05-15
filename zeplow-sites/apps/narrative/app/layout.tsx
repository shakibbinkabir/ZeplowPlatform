import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'narrative';

const playfair = localFont({
  src: '../public/fonts/PlayfairDisplay-Bold.woff2',
  variable: '--font-playfair',
  display: 'swap',
  preload: true,
});

// Manrope is a variable font — one file serves the full 400-700 weight range
// via font-variation-settings. Saves ~75KB vs. four static weight files.
const manrope = localFont({
  src: '../public/fonts/Manrope-Variable.woff2',
  variable: '--font-manrope',
  display: 'swap',
  weight: '400 700',
  preload: true,
});

export const metadata: Metadata = {
  metadataBase: new URL('https://narrative.zeplow.com'),
  title: 'Zeplow Narrative — Stories that sell.',
  description:
    'Brand storytelling, identity & content systems. We turn businesses into stories worth following.',
  icons: {
    icon: '/favicon.ico',
    apple: '/apple-touch-icon.png',
  },
  openGraph: {
    images: ['/og-default.jpg'],
  },
};

export default async function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const config = await getSiteConfig(SITE_KEY);

  return (
    <html lang="en" className={`${playfair.variable} ${manrope.variable}`}>
      <body className="bg-background text-text font-body antialiased">
        <Navigation
          siteName={config.site_name}
          items={config.nav_items}
          ctaText={config.cta_text}
          ctaUrl={config.cta_url}
          siteKey={SITE_KEY}
          logoLight="/logo-light.png"
          logoDark="/logo.png"
        />
        {children}
        <Footer
          links={config.footer_links}
          text={config.footer_text}
          socialLinks={config.social_links}
          contactEmail={config.contact_email}
          siteKey={SITE_KEY}
        />
      </body>
    </html>
  );
}
