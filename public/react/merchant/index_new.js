import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { render } from 'react-dom';
import { Tabs } from 'react-tabs';
import 'styles/merchant';

import 'rzp/utils/polyfills';
import store from './store';

import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';

import App from './containers/App';

window.React = React;
window.ReactDOM = ReactDOM;

Tabs.setUseDefaultStyles(false);

render(
  <Provider store={store}>
    <div id="react_root" class="react-root">
      <App />
      <ModalDialog />
      <Notifications />
    </div>
  </Provider>,
  document.getElementById('react-root')
);
