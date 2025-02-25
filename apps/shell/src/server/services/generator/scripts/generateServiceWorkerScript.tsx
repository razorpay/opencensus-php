import React from 'react';

// Todo: Test in prod, with asset cdn url
export const generateServiceWorkerScript = () => {
  return (
    <script
      key="service-worker"
      type="module"
      dangerouslySetInnerHTML={{
        __html: `
        import { Workbox } from 'https://storage.googleapis.com/workbox-cdn/releases/6.4.1/workbox-window.prod.mjs';
        if ('serviceWorker' in navigator) {
          const domain = window.location.origin;
          const serviceWorkerUrl = domain + '/dist/sw-utils/sw-merchant.js';
          const wb = new Workbox(serviceWorkerUrl);
          wb.register();
        }
    `,
      }}
    />
  );
};
