import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'parent';

const playfair = localFont({
  src: '../public/fonts/PlayfairDisplay-Bold.woff2',
  variable: '--font-playfair',
  display: 'swap',
});

const manrope = localFont({
  src: [
    { path: '../public/fonts/Manrope-Regular.woff2', weight: '400' },
    { path: '../public/fonts/Manrope-Medium.woff2', weight: '500' },
    { path: '../public/fonts/Manrope-SemiBold.woff2', weight: '600' },
    { path: '../public/fonts/Manrope-Bold.woff2', weight: '700' },
  ],
  variable: '--font-manrope',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL('https://zeplow.com'),
  icons: {
    icon: '/favicon.ico',
    apple: '/apple-touch-icon.png',
  },
  openGraph: {
    images: ['/og-default.png'],
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
