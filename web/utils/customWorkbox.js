import { clientsClaim } from 'workbox-core';
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BroadcastUpdatePlugin } from 'workbox-broadcast-update';

const ENABLED_CDN_ASSETS = /https:\/\/cdn\.razorpay\.com\/dashboard\/dist\/(js|css)?\/.*\.(js|css)?$/;
const ENABLED_FONTS = /\.(woff|woff2)?$/;

const ENABLED_STATIC_ASSETS = ['/static/analytics/bundle.js', '/static/assets/holidays.js'];

clientsClaim();
self.skipWaiting();

const __WB_MANIFEST = self.__WB_MANIFEST || [];

precacheAndRoute(__WB_MANIFEST);

registerRoute(
  ({ url: { href = '' } }) => ENABLED_CDN_ASSETS.test(href),
  new CacheFirst({
    cacheName: 'static-chunks',
    plugins: [
      new BroadcastUpdatePlugin(),
      new CacheableResponsePlugin({
        statuses: [200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 15,
        maxEntries: __WB_MANIFEST?.length || 200,
        matchOptions: {
          ignoreVary: true,
        },
      }),
    ],
  }),
);

registerRoute(
  ({ url: { pathname } }) => ENABLED_STATIC_ASSETS.indexOf(pathname) > -1,
  new StaleWhileRevalidate({
    cacheName: 'static-assets',
    plugins: [new BroadcastUpdatePlugin()],
  }),
);

registerRoute(
  ({ url: { origin, pathname } }) =>
    origin === 'https://fonts.googleapis.com' ||
    origin === 'https://fonts.gstatic.com' ||
    ENABLED_FONTS.test(pathname),
  new CacheFirst({
    cacheName: 'fonts',
    plugins: [
      new CacheableResponsePlugin({
        statuses: [200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
        maxEntries: 8,
        matchOptions: {
          ignoreVary: true,
        },
      }),
    ],
  }),
);
