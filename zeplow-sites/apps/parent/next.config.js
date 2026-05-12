/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  trailingSlash: true,
  // No hardcoded env block — Cloudflare Pages env vars flow through.
  // For local dev without a running API, client.ts falls back to mock-data
  // via try/catch.
  images: {
    unoptimized: true,
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
  typescript: {
    ignoreBuildErrors: true,
  },
  eslint: {
    ignoreDuringBuilds: true,
  },
}

module.exports = nextConfig
