import { clientsClaim } from 'workbox-core';
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BroadcastUpdatePlugin } from 'workbox-broadcast-update';

const ENABLED_CDN_ASSETS = /https:\/\/cdn\.razorpay\.com\/dashboard\/dist\/(js|css)?\/.*\.(js|css)?$/;
const ENABLED_FONTS = /\.(woff|woff2)?$/;

clientsClaim();
self.skipWaiting();

precacheAndRoute(self.__WB_MANIFEST);

registerRoute(
  ({ url: { pathname } }) => ENABLED_CDN_ASSETS.test(pathname),
  new CacheFirst({
    cacheName: 'static-chunks',
    plugins: [
      new BroadcastUpdatePlugin(),
      new CacheableResponsePlugin({
        statuses: [0, 200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
      }),
    ],
  }),
);

registerRoute(
  new RegExp('https://cdn\\.razorpay\\.com.*/dist/(merchant-entry|merchantLA-entry)?\\.*\\.(js)?$'),
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
        statuses: [0, 200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
      }),
    ],
  }),
);
