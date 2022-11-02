import { Route, registerRoute, Router } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BroadcastUpdatePlugin } from 'workbox-broadcast-update';
import { precacheMatcher, precacheHandler } from './precache';
import { CacheKeys } from './cache';
const ENABLED_CDN_ASSETS = /https:\/\/cdn\.razorpay\.com\/dashboard\/dist\/(js|css)?\/.*\.(js|css)?$/;
const ENABLED_FONTS = /\.(woff|woff2)?$/;

const ENABLED_STATIC_ASSETS = ['/static/analytics/bundle.js', '/static/assets/holidays.js'];

const registerPrecacheRoute = () => {
  const router = new Router();
  router.registerRoute(new Route(precacheMatcher, precacheHandler));
  router.addFetchListener();
};

export const init = () => {
  registerPrecacheRoute();
  registerRoute(
    ({ url: { href = '' } }) => ENABLED_CDN_ASSETS.test(href),
    new CacheFirst({
      cacheName: CacheKeys.Chunks,
      plugins: [
        new BroadcastUpdatePlugin(),
        new CacheableResponsePlugin({
          statuses: [200],
        }),
        new ExpirationPlugin({
          maxAgeSeconds: 60 * 60 * 24 * 15,
          maxEntries: 5,
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
      cacheName: CacheKeys.Assets,
      plugins: [new BroadcastUpdatePlugin()],
    }),
  );

  registerRoute(
    ({ url: { origin, pathname } }) =>
      origin === 'https://fonts.googleapis.com' ||
      origin === 'https://fonts.gstatic.com' ||
      ENABLED_FONTS.test(pathname),
    new CacheFirst({
      cacheName: CacheKeys.Fonts,
      plugins: [
        new CacheableResponsePlugin({
          statuses: [200],
        }),
        new ExpirationPlugin({
          maxAgeSeconds: 60 * 60 * 24 * 30,
          maxEntries: 4,
          matchOptions: {
            ignoreVary: true,
          },
        }),
      ],
    }),
  );
};
