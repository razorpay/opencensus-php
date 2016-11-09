import 'merchant/styles/layout.styl'
import React from 'react'
import ReactDOM from 'react-dom'
import { Provider } from 'react-redux'
import NgRouterProvider from 'rzp/Providers/NgRouterProvider'
import SessionProvider from './SessionProvider'
import store from './store'

import InvoicesListContainer from './containers/Invoices/List'
import InvoicesNewContainer from './containers/Invoices/New'
import SubscriptionsListContainer from './containers/Subscriptions/List'
import SubscriptionsNewContainer from './containers/Subscriptions/New'

import DFaqApi from 'dfaqapi'
import customers from './mocks/customers'
import plans from './mocks/plans'
import subscriptions from './mocks/subscriptions'

let fakeApi = new DFaqApi({
  factories: {
    plans,
    customers,
    subscriptions
  }
})

fakeApi.createList('customers', 10)
fakeApi.createList('plans', 5)
fakeApi.createList('subscriptions', 3)

fakeApi.get('/test/subscriptions', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('subscriptions').data
    }
  }
})

fakeApi.get('/test/customers', (db, request) => {
  return {
    success: true,
    data: {
      count: 10,
      items: db.getCollection('customers').data
    }
  }
})


// This is required for ngReact. Remove this finally
window.React = React
window.ReactDOM = ReactDOM

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

function contextProvider({ component, ngRouter, store, user }) {
  return (props) => {
    return React.createElement(
      Provider,
      { store },
      React.createElement(
        NgRouterProvider,
        { ngRouter },
        React.createElement(
          SessionProvider,
          { user },
          React.createElement(component, props)
        )
      )
    )
  }
}

function createNgDirective(directiveName, component, ...args) {
  app.directive(directiveName, [
    'reactDirective',
    '$state',
    'user',
    (reactDirective, $state, user) => {
      return reactDirective(contextProvider({
        component,
        ngRouter: $state,
        store,
        user
      }), ...args)
    }
  ])
}

createNgDirective('invoicesList', InvoicesListContainer)
createNgDirective('invoicesNew', InvoicesNewContainer)
createNgDirective('subscriptionsList', SubscriptionsListContainer)
createNgDirective('subscriptionsNew', SubscriptionsNewContainer)
