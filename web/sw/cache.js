const version = 'v1';

export const CacheKeys = {
  Precache: `precache-chunks:${version}`,
  Chunks: `static-chunks:${version}`,
  Assets: `static-assets:${version}`,
  Fonts: `fonts:${version}`,
};

export const deleteOlderVersion = () => {
  const validCacheNames = Object.values(CacheKeys);
  caches.keys().then((keys) =>
    keys.forEach((subKeys) => {
      validCacheNames.indexOf(subKeys) > -1 || caches.delete(subKeys);
    }),
  );
};
