import { combineReducers } from 'redux'
import { reducer as formReducer } from 'redux-form'
import invoicesReducer from 'merchant/modules/invoices/list'
import invoiceReducer from 'merchant/modules/invoices/new'

export default combineReducers({
  form: formReducer,
  invoice: invoiceReducer,
  invoices: invoicesReducer
})
