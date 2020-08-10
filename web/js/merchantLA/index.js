__webpack_public_path__ = (window.cdnDashboardUrl || '') + `/dist/`;
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import 'core-js/es/map';
import 'core-js/es/set';
import React from 'react';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import 'common/utils/polyfills';
import store from 'merchantLA/store';

import ConfirmModalProvider from 'common/ui/ConfirmModal/ConfirmModalProvider';

import App from 'merchantLA/containers/App';
import css from '../../css/merchant-la.styl';
import fontconfig from '../../dashboard.font';

render(
  <Provider store={store}>
    <ConfirmModalProvider>
      <Router basename="/app">
        <App />
      </Router>
    </ConfirmModalProvider>
  </Provider>,
  document.getElementById('react-root')
);
