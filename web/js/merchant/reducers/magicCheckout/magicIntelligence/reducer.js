import { merge, remove } from 'common/utils/immutable';
import { ACTIONS } from 'merchant/reducers/magicCheckout/magicIntelligence/actions';

const ERROR_MESSAGES = {
  EMAIL: 'Please enter email address in a proper format. e.g. abc@gmail.com',
  PHONE: 'Please enter a valid phone number with country code, e.g. +919988776655',
  ZIPCODE: 'Please enter valid 6 digit zipcode',
  IP: 'Please enter a valid IP Address, e.g. 123.123.123.123',
};

const initialState = {
  loading: true,
  items: [],
  error: null,
};

export function blocklistReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.FETCH_BLOCKLIST_PENDING:
      return merge(state, { items: [], loading: true });
    case ACTIONS.FETCH_BLOCKLIST_SUCCESS:
      return merge(state, {
        loading: false,
        items: action.payload.data.cod_eligibility_attributes ?? action.payload.data,
        error: null,
      });
    case ACTIONS.FETCH_BLOCKLIST_ERROR: {
      const errors = action.payload.errors;
      if (errors && errors[0].indexOf('email_attribute') !== -1) errors[0] = ERROR_MESSAGES.EMAIL;
      else if (errors && errors[0].indexOf('phone_attribute') !== -1)
        errors[0] = ERROR_MESSAGES.PHONE;
      else if (errors && errors[0].indexOf('zipcode_attribute') !== -1)
        errors[0] = ERROR_MESSAGES.ZIPCODE;
      else if (errors && errors[0].indexOf('ip_attribute') !== -1) errors[0] = ERROR_MESSAGES.IP;
      return merge(state, {
        loading: false,
        items: state.items,
        error: errors,
      });
    }
    case ACTIONS.UPLOAD_BLOCKLIST_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.UPLOAD_BLOCKLIST_SUCCESS:
      return merge(state, {
        loading: false,
        items: state.items,
        error: null,
      });
    case ACTIONS.UPLOAD_BLOCKLIST_ERROR:
      return merge(state, {
        loading: false,
        items: state.items,
        error: action.payload.errors,
      });
    case ACTIONS.DELETE_BLOCKLIST_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.DELETE_BLOCKLIST_SUCCESS: {
      const itemsList = remove(state.items, (item) => item.id === action.item_id);
      return merge(state, {
        loading: false,
        items: itemsList,
        error: null,
      });
    }
    case ACTIONS.DELETE_BLOCKLIST_ERROR:
      return merge(state, {
        loading: false,
        items: state.items,
        error: action.payload.errors,
      });
    default:
      return state;
  }
}

export function allowlistReducer(state = initialState, action) {
  switch (action.type) {
    case ACTIONS.FETCH_ALLOWLIST_PENDING:
      return merge(state, { items: [], loading: true });
    case ACTIONS.FETCH_ALLOWLIST_SUCCESS:
      return merge(state, {
        loading: false,
        items: action.payload.data.cod_eligibility_attributes ?? action.payload.data,
        error: null,
      });
    case ACTIONS.FETCH_ALLOWLIST_ERROR: {
      const errors = action.payload.errors;
      if (errors && errors[0].indexOf('email_attribute') !== -1) errors[0] = ERROR_MESSAGES.EMAIL;
      else if (errors && errors[0].indexOf('phone_attribute') !== -1)
        errors[0] = ERROR_MESSAGES.PHONE;
      return merge(state, {
        loading: false,
        items: state.items,
        error: action.payload.errors,
      });
    }
    case ACTIONS.UPLOAD_ALLOWLIST_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.UPLOAD_ALLOWLIST_SUCCESS:
      return merge(state, {
        loading: false,
        items: state.items,
        error: null,
      });
    case ACTIONS.UPLOAD_ALLOWLIST_ERROR:
      return merge(state, {
        loading: false,
        items: state.items,
        error: action.payload.errors,
      });
    case ACTIONS.DELETE_ALLOWLIST_PENDING:
      return merge(state, { loading: true });
    case ACTIONS.DELETE_ALLOWLIST_SUCCESS: {
      const itemsList = remove(state.items, (item) => item.id === action.item_id);
      return merge(state, {
        loading: false,
        items: itemsList,
        error: null,
      });
    }
    case ACTIONS.DELETE_ALLOWLIST_ERROR:
      return merge(state, {
        loading: false,
        items: state.items,
        error: action.payload.errors,
      });
    default:
      return state;
  }
}
