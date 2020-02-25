import 'babel-polyfill';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import 'common/utils/polyfills';
import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';
import ErrorBoundary from 'common/new-ui/ErrorBoundary';
import store from './store';
import App from './containers/App';

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
  document.getElementById('react-root')
);
