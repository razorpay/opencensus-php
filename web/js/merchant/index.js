import './public-paths';
// eslint-disable-next-line import/extensions
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import 'common/utils/polyfills';
import store from './store';
import App from './containers/App';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import '../../css/merchant.styl';
import '../../dashboard.font';
import { ViewportProvider } from 'merchant/hooks/useViewPort';

(async () => {
  if (localStorage.referrer === 'chrome-extension') {
    await import(/* webpackChunkName: "extension" */ './extension-entry');
  }
})();

render(
  <Provider store={store}>
    <ViewportProvider>
      <ConfirmModalProvider>
        <Router basename="/app">
          <ErrorBoundary>
            <App />
          </ErrorBoundary>
        </Router>
      </ConfirmModalProvider>
    </ViewportProvider>
  </Provider>,
  document.getElementById('react-root'),
);
