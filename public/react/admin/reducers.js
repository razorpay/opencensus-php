import { combineReducers } from 'redux'
import { reducer as formReducer } from 'redux-form'
import sessionReducer from 'merchant/modules/session'
import modalReducer from 'merchant/modules/modals'
import notificationsReducer from 'merchant/modules/notifications'

export default combineReducers({
  form: formReducer,
  session: sessionReducer,
  modal: modalReducer,
  notifications: notificationsReducer
})
