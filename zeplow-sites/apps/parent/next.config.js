/** @type {import('next').NextConfig} */
const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true,
  },
  transpilePackages: ['@zeplow/ui', '@zeplow/api', '@zeplow/config'],
}

module.exports = nextConfig
