import { combineReducers } from 'redux';
import { reducer as formReducer } from 'redux-form';
import modalReducer from 'rzp/modules/modals';
import notificationsReducer from 'rzp/modules/notifications';
import teamReducer from 'rzp/modules/team';

export default combineReducers({
  form: formReducer,
  modal: modalReducer,
  notifications: notificationsReducer,
  team: teamReducer,
});
