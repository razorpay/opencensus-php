import React from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import { render } from 'react-dom';

import 'rzp/utils/polyfills';
import store from './store';
import App from './containers/App';

window.React = React;
window.ReactDOM = ReactDOM;

debugger;
render(
  <Provider store={store}>
    <App />
  </Provider>,
  document.getElementById('react-root')
);
