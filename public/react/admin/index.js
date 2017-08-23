import 'rzp/utils/polyfills';
import 'styles/rzp/layout.styl';
import React, { Component } from 'react';
import ReactDOM from 'react-dom';
import { Provider } from 'react-redux';
import NgRouterProvider from 'rzp/Providers/NgRouterProvider';
import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider';
import ModalDialog from 'rzp/ui/ModalDialog';
import Notifications from 'rzp/ui/Notifications';
import store from './store';

import MerchantTeam from 'admin/containers/Merchant/Team';

// This is required for ngReact. Remove this finally
window.React = React;
window.ReactDOM = ReactDOM;

/*
 * Below is a transpiled version of
 *  <Provider store={store}>
 *    <App />
 *  </Provider>
 *
 * Since, the angular templates are not transipled, we do it manually below. These Provider
 * components facilitates the store to be available on all child components via react's context.
 * See
 *  1. https://github.com/reactjs/react-redux/blob/master/docs/api.md#provider-store
 *  2. https://facebook.github.io/react/docs/context.html
 */

function contextProvider({ component, ngRouter, store, user, modeFactory }) {
  return props => {
    return React.createElement(
      Provider,
      { store },
      React.createElement(
        NgRouterProvider,
        { ngRouter },
        React.createElement(
          ConfirmModalProvider,
          null,
          React.createElement(
            'div',
            null,
            React.createElement(component, props),
            React.createElement(ModalDialog),
            React.createElement(Notifications)
          )
        )
      )
    );
  };
}

function createNgDirective(directiveName, component, ...args) {
  app.directive(directiveName, [
    'reactDirective',
    '$state',
    'user',
    'modeFactory',
    (reactDirective, $state, user, modeFactory) => {
      return reactDirective(
        contextProvider({
          component,
          ngRouter: $state,
          store,
          user,
          modeFactory,
        }),
        ...args
      );
    },
  ]);
}

createNgDirective('merchantTeam', MerchantTeam, ['id']);
