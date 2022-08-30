import { clientsClaim } from 'workbox-core';
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BroadcastUpdatePlugin } from 'workbox-broadcast-update';

const STALE_WILE_STRATERGY_ENABLE = ['script', 'style'];

clientsClaim();
self.skipWaiting();

precacheAndRoute(self.__WB_MANIFEST);

registerRoute(
  ({ request: { destination } }) => STALE_WILE_STRATERGY_ENABLE.indexOf(destination) > -1,
  new StaleWhileRevalidate({
    cacheName: 'static-assets',
    plugins: [new BroadcastUpdatePlugin()],
  }),
);

registerRoute(
  ({ url: { origin } }) =>
    origin === 'https://fonts.googleapis.com' || origin === 'https://fonts.gstatic.com',
  new CacheFirst({
    cacheName: 'fonts',
    plugins: [
      new CacheableResponsePlugin({
        statuses: [0, 200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
      }),
    ],
  }),
);
