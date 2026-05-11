/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  trailingSlash: true,
  env: {
    NEXT_PUBLIC_ZEPLOW_MOCK_ONLY: '1',
  },
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
