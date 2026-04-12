/**
 * Returns the URL for a specific Spatie MediaLibrary conversion.
 * Replaces the filename in the base URL with conversions/{original-filename}-{conversion}.{ext}
 *
 * Example:
 *   getImageUrl('https://cms.zeplow.com/storage/media/1/hero.jpg', 'medium')
 *   → 'https://cms.zeplow.com/storage/media/1/conversions/hero-medium.jpg'
 */
export function getImageUrl(
  baseUrl: string,
  conversion?: 'large' | 'medium' | 'thumbnail'
): string {
  if (!conversion) return baseUrl;

  try {
    const url = new URL(baseUrl);
    const pathParts = url.pathname.split('/');
    const filename = pathParts.pop()!;
    const lastDot = filename.lastIndexOf('.');
    const name = filename.substring(0, lastDot);
    const ext = filename.substring(lastDot);
    pathParts.push('conversions', `${name}-${conversion}${ext}`);
    url.pathname = pathParts.join('/');
    return url.toString();
  } catch {
    return baseUrl;
  }
}
