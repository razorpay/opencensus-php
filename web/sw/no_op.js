// eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars
const menifest = self.__WB_MANIFEST;

self.addEventListener('install', () => {
  self.skipWaiting();
});

self.addEventListener('activate', () => {
  self.registration
    .unregister()
    .then(() => {
      return self.clients.matchAll();
    })
    .then((clients) => {
      clients.forEach((client) => client.navigate(client.url));
    });
});
