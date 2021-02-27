__webpack_public_path__ = (window.cdnDashboardUrl || '') + `/dist/`;
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import { analyticsTrack } from 'common/utils/analytics';
import LocalStorageService from 'common/utils/localStorage';
import 'common/utils/polyfills';
import store from './store';
import App from './containers/App';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import '../../css/merchant.styl';
import '../../dashboard.font';

(async () => {
  if (localStorage.referrer === 'chrome-extension') {
    await import(/* webpackChunkName: "extension" */ './extension-entry');
  }
})();

// analyticsTrack.init({
//   lumberjackAppName: 'pg-dashboard',
//   lumberjackApiKey: window.LUMBERJACK_API_KEY,
//   lumberjackApiUrl: window.LUMBERJACK_API_URL,
//   segmentApiKey: window.SEGMENT_API_KEY,
// });

render(
  <Provider store={store}>
    <ConfirmModalProvider>
      <Router basename="/app">
        <ErrorBoundary>
          <App />
        </ErrorBoundary>
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root'),
);
