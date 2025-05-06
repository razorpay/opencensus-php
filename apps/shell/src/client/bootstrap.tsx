import 'whatwg-fetch';
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import '@fontsource/lato/400.css';
import '@fontsource/lato/700.css';
import React from 'react';
import { loadableReady } from '@loadable/component';
import { hydrate, DehydratedState } from '@tanstack/react-query';
import { queryClient } from '@apps/shell/src/shared/store/queryClient';
import hydrateApp from './utils/hydrateApp';
import { ShellBrowserProvider } from './contexts/ShellBrowserProvider';
import App from '@apps/shell/src/app';
import { initSentry } from './utils/sentry';
import { IS_PRODUCTION } from '../env';

declare global {
  interface Window {
    __REACT_QUERY_STATE__: DehydratedState; // Adjust type as per your actual data structure.
  }
}

if (IS_PRODUCTION && __APP_VERSION__) {
  initSentry('one-dashboard');
}

const rootHTMLElement = document.getElementById('root');

if (!rootHTMLElement) {
  throw new Error('Failed to find the root element');
}

const dehydratedState = window.__REACT_QUERY_STATE__ || {};

hydrate(queryClient, dehydratedState);

const app = (
  <ShellBrowserProvider queryClient={queryClient}>
    <App />
  </ShellBrowserProvider>
);

loadableReady(() => {
  hydrateApp(app, rootHTMLElement);
});
