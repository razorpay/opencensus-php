import { combineReducers } from 'redux'
import { reducer as formReducer } from 'redux-form'
import invoicesReducer from 'merchant/modules/invoices/list'
import invoiceDetailsReducer from 'merchant/modules/invoices/details'
import subscriptionsReducer from 'merchant/modules/subscriptions'
import plansReducer from 'merchant/modules/plans'
import customersReducer from 'merchant/modules/customers'
import itemsReducer from 'merchant/modules/items'

export default combineReducers({
  form: formReducer,
  invoices: invoicesReducer,
  invoice: invoiceDetailsReducer,
  subscriptions: subscriptionsReducer,
  plans: plansReducer,
  customers: customersReducer,
  items: itemsReducer
})
