import 'rzp/utils/polyfills'
import 'merchant/styles/layout.styl'
import React from 'react'
import ReactDOM from 'react-dom'
import { Provider } from 'react-redux'
import NgRouterProvider from 'rzp/Providers/NgRouterProvider'
import ConfirmModalProvider from 'rzp/ui/ConfirmModal/ConfirmModalProvider'
import ModalDialog from 'rzp/ui/ModalDialog'
import Notifications from 'rzp/ui/Notifications'
import SessionProvider from './SessionProvider'
import store from './store'

// import './mocks/faker'

import CustomersListContainer from './containers/Customers/List'
import ItemsListContainer from './containers/Items/List'
import InvoicesListContainer from './containers/Invoices/List'
import InvoicesNewContainer from './containers/Invoices/New'
import InvoiceDetailsContainer from './containers/Invoices/Details'

import OrdersListContainer from './containers/Orders/List'
import OrderDetailsContainer from './containers/Orders/Details'
import WebhooksContainer from './containers/Webhooks/List'
import AddFundsContainer from './containers/AddFunds'

import SettlementsListContainer from './containers/Settlements/List'
import SettlementDetailsContainer from './containers/Settlements/Details'

import CreditsContainer from './containers/Credits/List'

import RefundsListContainer from './containers/Refunds/List'
import RefundDetailsContainer from './containers/Refunds/Details'
// import PlansListContainer from './containers/Plans/List'
// import SubscriptionsListContainer from './containers/Subscriptions/List'
// import SubscriptionsNewContainer from './containers/Subscriptions/New'


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

function contextProvider({ component, ngRouter, store, user, modeFactory }) {
  return (props) => {
    return React.createElement(
      Provider,
      { store },
      React.createElement(
        NgRouterProvider,
        { ngRouter },
        React.createElement(
          SessionProvider,
          { user, modeFactory },
          React.createElement(
            ConfirmModalProvider,
            null,
            React.createElement(
              'div',
              null,
              React.createElement(component, props),
              React.createElement(ModalDialog),
              React.createElement(Notifications),
            )
          )
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
    'modeFactory',
    (reactDirective, $state, user, modeFactory) => {
      return reactDirective(contextProvider({
        component,
        ngRouter: $state,
        store,
        user,
        modeFactory,
      }), ...args)
    }
  ])
}

createNgDirective('invoicesNew', InvoicesNewContainer, ['id'])
createNgDirective('invoicesList', InvoicesListContainer)
createNgDirective('invoiceDetail', InvoiceDetailsContainer, ['id'])
createNgDirective('customersList', CustomersListContainer)
createNgDirective('itemsList', ItemsListContainer)
createNgDirective('creditsNew', CreditsContainer)

createNgDirective('ordersList', OrdersListContainer)
createNgDirective('orderDetails', OrderDetailsContainer, ['id'])

createNgDirective('settlementsList', SettlementsListContainer)
createNgDirective('settlementDetails', SettlementDetailsContainer, ['id'])

createNgDirective('webhooksList', WebhooksContainer)
createNgDirective('addFunds', AddFundsContainer)

createNgDirective('refundsList', RefundsListContainer)
createNgDirective('refundDetails', RefundDetailsContainer, ['id'])

// createNgDirective('subscriptionsList', SubscriptionsListContainer)
// createNgDirective('subscriptionsNew', SubscriptionsNewContainer)
// createNgDirective('plansList', PlansListContainer)
