import { getSiteConfig } from '@zeplow/api';
import { Navigation, Footer } from '@zeplow/ui';
import localFont from 'next/font/local';
import type { Metadata } from 'next';
import './globals.css';

const SITE_KEY = 'logic';

const jetbrains = localFont({
  src: '../public/fonts/JetBrainsMono-Bold.woff2',
  variable: '--font-jetbrains',
  display: 'swap',
});

const inter = localFont({
  src: [
    { path: '../public/fonts/Inter-Regular.woff2', weight: '400', style: 'normal' },
    { path: '../public/fonts/Inter-Medium.woff2', weight: '500', style: 'normal' },
    { path: '../public/fonts/Inter-SemiBold.woff2', weight: '600', style: 'normal' },
    { path: '../public/fonts/Inter-Bold.woff2', weight: '700', style: 'normal' },
  ],
  variable: '--font-inter',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL('https://logic.zeplow.com'),
  title: 'Zeplow Logic — Build once. Run forever.',
  description:
    'Technology, automation & AI systems. We replace operational chaos with systems that run boringly well.',
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
    <html lang="en" className={`${jetbrains.variable} ${inter.variable}`}>
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
