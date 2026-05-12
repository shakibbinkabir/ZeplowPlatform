// Cloudflare Worker that proxies public read traffic from CF Pages build
// agents to api.zeplow.com. Workaround: Imunify360 on the cPanel host blocks
// CF Pages build runner IPs but allows Cloudflare egress IPs. Routing
// build-time fetches through this Worker swaps the source IP and gets us
// past the WAF without hosting-side changes.
//
// Only /sites/v1/* (public read endpoints) are proxied. /internal/v1/* and
// everything else returns 404 — those require bearer auth and have no
// business going through a public proxy.

const ORIGIN = 'https://api.zeplow.com';
const ALLOWED_PREFIX = '/sites/v1/';

export default {
  async fetch(request) {
    const url = new URL(request.url);

    if (!url.pathname.startsWith(ALLOWED_PREFIX)) {
      return new Response('Not found', { status: 404 });
    }

    const target = ORIGIN + url.pathname + url.search;

    const forwardHeaders = new Headers(request.headers);
    forwardHeaders.delete('host');
    forwardHeaders.delete('cf-connecting-ip');
    forwardHeaders.delete('cf-ipcountry');
    forwardHeaders.delete('cf-ray');
    forwardHeaders.delete('cf-visitor');

    const upstream = await fetch(target, {
      method: request.method,
      headers: forwardHeaders,
      body:
        request.method === 'GET' || request.method === 'HEAD'
          ? undefined
          : request.body,
      redirect: 'follow',
    });

    return new Response(upstream.body, {
      status: upstream.status,
      statusText: upstream.statusText,
      headers: upstream.headers,
    });
  },
};
