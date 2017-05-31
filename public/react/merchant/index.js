import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';
import { Tabs } from 'react-tabs';
import 'styles/merchant';

import 'rzp/utils/polyfills';
import store from './store';

import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider';

import App from './containers/App';

window.React = React;
window.ReactDOM = ReactDOM;

Tabs.setUseDefaultStyles(false);

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
