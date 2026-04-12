import React from 'react';
import type { FooterLinkGroup } from '@zeplow/api';

interface FooterProps {
  links: FooterLinkGroup[];
  text: string;
  socialLinks: Record<string, string>;
  contactEmail: string;
  siteKey: string;
}

export function Footer({
  links,
  text,
  socialLinks,
  contactEmail,
}: FooterProps) {
  return (
    <footer className="border-t border-text/[0.06] bg-white">
      <div className="mx-auto max-w-6xl px-6 lg:px-8">
        {/* Main footer */}
        <div className="grid gap-12 py-20 md:grid-cols-12">
          {/* Brand column */}
          <div className="md:col-span-4">
            <img
              src="/logo.png"
              alt="Zeplow"
              height={24}
              className="h-6 w-auto"
            />
            <p className="mt-3 text-sm leading-relaxed text-text/40">
              Story. Systems. Ventures.
            </p>
            {contactEmail && (
              <a
                href={`mailto:${contactEmail}`}
                className="mt-4 inline-block text-sm text-text/40 transition-colors hover:text-accent"
              >
                {contactEmail}
              </a>
            )}
          </div>

          {/* Link groups */}
          {links.map((group) => (
            <div key={group.group_title} className="md:col-span-2">
              <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-text/30">
                {group.group_title}
              </p>
              <ul className="mt-5 space-y-3">
                {group.links.map((link) => (
                  <li key={link.url}>
                    <a
                      href={link.url}
                      className="text-sm text-text/50 transition-colors duration-200 hover:text-primary"
                      {...(link.url.startsWith('http')
                        ? { target: '_blank', rel: 'noopener noreferrer' }
                        : {})}
                    >
                      {link.label}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          ))}

          {/* Socials */}
          {Object.keys(socialLinks).length > 0 && (
            <div className="md:col-span-2">
              <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-text/30">
                Connect
              </p>
              <ul className="mt-5 space-y-3">
                {Object.entries(socialLinks).map(([platform, url]) => (
                  <li key={platform}>
                    <a
                      href={url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-sm capitalize text-text/50 transition-colors duration-200 hover:text-primary"
                    >
                      {platform}
                    </a>
                  </li>
                ))}
              </ul>
            </div>
          )}
        </div>

        {/* Bottom bar */}
        <div className="border-t border-text/[0.06] py-8">
          <p className="text-[12px] text-text/30">{text}</p>
        </div>
      </div>
    </footer>
  );
}
