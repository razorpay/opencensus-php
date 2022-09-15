import { clientsClaim } from 'workbox-core';
import { precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { StaleWhileRevalidate, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';
import { BroadcastUpdatePlugin } from 'workbox-broadcast-update';

const RAZORPAY_CDN_ASSETS = /https:\/\/cdn\.razorpay\.com\/dashboard\/dist\/(js|css)?\/.*\.(js|css)?$/;
const ENABLED_FONTS = /\.(woff|woff2)?$/;
const THIRD_PARTY_DOMAINS = [
  // for other razorpay assets like holdidays, static
  'https://cdn.razorpay.com',

  // other third party domains
  'https://wchat.freshchat.com',
  'https://static.hotjar.com',
  'https://js.refiner.io',
  'https://dynamic.criteo.com',
  'https://www.redditstatic.com',
  'https://static.ads-twitter.com',
  'https://bat.bing.com',
  'https://js.hs-scripts.com',
  'https://js.usemessages.com',
  'https://js.hs-analytics.net',
  'https://js.hsadspixel.net',
  'https://js.hs-banner.com',
  'https://js.hsleadflows.net',
  'https://www.clarity.ms',
  'https://d2r1yp2w7bby2u.cloudfront.net',
  'https://in.wzrkt.com',
  'https://cdn.yellowmessenger.com',
  'https://js-na1.hs-scripts.com',
  'https://code.jquery.com',
  'https://www.youtube.com',
];

clientsClaim();
self.skipWaiting();

precacheAndRoute(self.__WB_MANIFEST);

registerRoute(
  ({ url: { href = '' } }) => RAZORPAY_CDN_ASSETS.test(href),
  new CacheFirst({
    cacheName: 'static-chunks',
    plugins: [
      new BroadcastUpdatePlugin(),
      new CacheableResponsePlugin({
        statuses: [0, 200],
      }),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
        purgeOnQuotaError: true,
      }),
    ],
  }),
);

registerRoute(
  ({ url: { origin }, request }) => {
    return THIRD_PARTY_DOMAINS.includes(origin) && request.destination === 'script';
  },
  new StaleWhileRevalidate({
    cacheName: 'static-assets-thirdparty',
    plugins: [
      new BroadcastUpdatePlugin(),
      new ExpirationPlugin({
        maxAgeSeconds: 60 * 60 * 24 * 30,
        maxEntries: 50,
        purgeOnQuotaError: true,
      }),
    ],
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
        maxEntries: 10,
        purgeOnQuotaError: true,
      }),
    ],
  }),
);
