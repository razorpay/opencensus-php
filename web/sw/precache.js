import { PrecacheController } from 'workbox-precaching';
import { CacheFirst } from 'workbox-strategies';
import { CacheKeys } from './cache';

const cacheFirst = new CacheFirst({
  cacheName: CacheKeys.Precache,
});

export const precacheController = new PrecacheController({
  cacheName: CacheKeys.Precache,
});

export const init = (__WB_MANIFEST = []) => {
  precacheController.addToCacheList(__WB_MANIFEST);
};

export const precacheMatcher = ({ url }) => {
  return Boolean(precacheController.getCacheKeyForURL(url.href));
};

export const precacheHandler = ({ request, event }) => {
  const cacheKey = precacheController.getCacheKeyForURL(request.url);

  return cacheFirst.handle({
    request: new Request(cacheKey),
    event,
  });
};
