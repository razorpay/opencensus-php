import { clientsClaim } from 'workbox-core';
import * as precache from './precache';
import * as router from './router';
import { deleteOlderVersion } from './cache';

clientsClaim();
self.skipWaiting();

const __WB_MANIFEST = self.__WB_MANIFEST || [];

precache.init(__WB_MANIFEST);
router.init();

self.addEventListener('install', (event) => {
  event.waitUntil(precache.precacheController.install(event));
});

self.addEventListener('activate', (event) => {
  const activateEvent = async () => {
    await Promise.all([precache.precacheController.activate(event), deleteOlderVersion()]);
  };
  event.waitUntil(activateEvent());
});
