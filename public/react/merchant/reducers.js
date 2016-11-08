import { combineReducers } from 'redux'
import { reducer as formReducer } from 'redux-form'
import invoicesReducer from 'merchant/modules/invoices/list'
import invoiceReducer from 'merchant/modules/invoices/new'
import subscriptionsReducer from 'merchant/modules/subscriptions/list'

export default combineReducers({
  form: formReducer,
  invoice: invoiceReducer,
  invoices: invoicesReducer,
  subscriptions: subscriptionsReducer
})
