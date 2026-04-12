'use client';

import React, { useState, useEffect } from 'react';
import type { NavItem } from '@zeplow/api';

interface NavigationProps {
  siteName: string;
  items: NavItem[];
  ctaText: string;
  ctaUrl: string;
  siteKey: string;
}

export function Navigation({
  siteName,
  items,
  ctaText,
  ctaUrl,
}: NavigationProps) {
  const [mobileOpen, setMobileOpen] = useState(false);
  const [scrolled, setScrolled] = useState(false);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 10);
    window.addEventListener('scroll', onScroll, { passive: true });
    return () => window.removeEventListener('scroll', onScroll);
  }, []);

  return (
    <header
      className={`fixed top-0 left-0 right-0 z-50 transition-all duration-300 ${
        scrolled
          ? 'bg-white/80 backdrop-blur-xl shadow-[0_1px_0_rgba(0,0,0,0.04)]'
          : 'bg-transparent'
      }`}
    >
      <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-5 lg:px-8">
        <a
          href="/"
          className="transition-opacity hover:opacity-70"
        >
          <img
            src="/logo.png"
            alt={siteName}
            height={28}
            className="h-7 w-auto"
          />
        </a>

        {/* Desktop */}
        <div className="hidden items-center gap-10 md:flex">
          {items.map((item) => (
            <a
              key={item.url}
              href={item.url}
              className="relative text-[13px] font-medium uppercase tracking-[0.08em] text-text/60 transition-colors duration-200 hover:text-primary"
              {...(item.is_external
                ? { target: '_blank', rel: 'noopener noreferrer' }
                : {})}
            >
              {item.label}
            </a>
          ))}
          <a
            href={ctaUrl}
            className="rounded-full bg-primary px-5 py-2 text-[13px] font-medium tracking-wide text-white transition-all duration-200 hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/20"
          >
            {ctaText}
          </a>
        </div>

        {/* Mobile toggle */}
        <button
          className="relative z-50 flex h-8 w-8 items-center justify-center md:hidden"
          onClick={() => setMobileOpen(!mobileOpen)}
          aria-label="Toggle menu"
        >
          <div className="flex flex-col gap-[5px]">
            <span
              className={`block h-[1.5px] w-5 bg-primary transition-all duration-300 ${
                mobileOpen ? 'translate-y-[3.25px] rotate-45' : ''
              }`}
            />
            <span
              className={`block h-[1.5px] w-5 bg-primary transition-all duration-300 ${
                mobileOpen ? '-translate-y-[3.25px] -rotate-45' : ''
              }`}
            />
          </div>
        </button>
      </nav>

      {/* Mobile overlay */}
      <div
        className={`fixed inset-0 z-40 bg-white transition-all duration-500 md:hidden ${
          mobileOpen
            ? 'opacity-100 pointer-events-auto'
            : 'opacity-0 pointer-events-none'
        }`}
      >
        <div className="flex h-full flex-col items-center justify-center gap-8">
          {items.map((item) => (
            <a
              key={item.url}
              href={item.url}
              onClick={() => setMobileOpen(false)}
              className="text-2xl font-light tracking-tight text-text/70 transition-colors hover:text-primary"
              {...(item.is_external
                ? { target: '_blank', rel: 'noopener noreferrer' }
                : {})}
            >
              {item.label}
            </a>
          ))}
          <a
            href={ctaUrl}
            onClick={() => setMobileOpen(false)}
            className="mt-4 rounded-full bg-primary px-8 py-3 text-sm font-medium text-white"
          >
            {ctaText}
          </a>
        </div>
      </div>
    </header>
  );
}
