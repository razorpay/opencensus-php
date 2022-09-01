import { Workbox } from 'workbox-window';
import User from 'merchant/models/User';

const isWorkboxEnable = () => {
  const user = new User(window.rzp_user || {});
  return user?.isWorkboxEnable;
};

const unregister = () => {
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker
      .getRegistrations()
      .then((registrations) => {
        for (const registration of registrations) {
          registration.unregister();
        }
      })
      .catch(() => {});
  }
};

export default function registerServiceWorker() {
  if (process.env.PUBLIC_ENV === 'production') {
    if ('serviceWorker' in navigator) {
      const url = process.env.PROJECT && `/sw-${process.env.PROJECT}.js`;
      if (isWorkboxEnable() && url) {
        const workboxInstance = new Workbox('/sw.js');
        workboxInstance.addEventListener('installed', () => {
          console.log('Service Worker is ready');
        });
        addEventListener('message', (event) => {
          if (event.data.type === 'CACHE_UPDATED') {
            const { updatedURL } = event.data.payload;
            console.log(`A newer version of ${updatedURL} is available!`);
          }
        });
        workboxInstance.register();
      } else {
        unregister();
      }
    }
  }
}
