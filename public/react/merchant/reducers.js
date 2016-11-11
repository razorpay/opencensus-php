import { combineReducers } from 'redux'
import { reducer as formReducer } from 'redux-form'
import invoicesReducer from 'merchant/modules/invoices/list'
import invoiceReducer from 'merchant/modules/invoices/new'
import subscriptionsReducer from 'merchant/modules/subscriptions/list'
import plansReducer from 'merchant/modules/plans'
import customersReducer from 'merchant/modules/customers/list'

export default combineReducers({
  form: formReducer,
  invoice: invoiceReducer,
  invoices: invoicesReducer,
  subscriptions: subscriptionsReducer,
  plans: plansReducer,
  customers: customersReducer
})
