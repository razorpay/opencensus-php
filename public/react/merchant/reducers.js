import { combineReducers } from 'redux'
import invoicesReducer from 'merchant/modules/invoices'

export default combineReducers({
  invoices: invoicesReducer
})
