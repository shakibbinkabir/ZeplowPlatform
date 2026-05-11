import type { Config } from 'tailwindcss';

const config: Config = {
  content: [
    './app/**/*.{ts,tsx}',
    './components/**/*.{ts,tsx}',
    '../../packages/ui/src/**/*.{ts,tsx}',
  ],
  theme: {
    extend: {
      colors: {
        primary: '#081f1a',
        background: '#f4f4f4',
        text: '#081f1a',
        accent: '#00b894',
        error: '#ff7675',
      },
      fontFamily: {
        heading: ['var(--font-jetbrains)', 'ui-monospace', 'SFMono-Regular', 'monospace'],
        body: ['var(--font-inter)', 'system-ui', 'sans-serif'],
        mono: ['var(--font-jetbrains)', 'ui-monospace', 'SFMono-Regular', 'monospace'],
      },
    },
  },
  plugins: [],
};

export default config;
