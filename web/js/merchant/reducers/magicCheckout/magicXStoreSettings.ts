import type { MagicXConfigDataFormData } from 'merchant/views/MagicCheckout/MagicXStoreSettings/types';

export const INITIAL_STATE: MagicXConfigDataFormData = {
  themeColor: '#000000',
  status: false,
  emailField: 'hidden',
  isLoginMandatory: false,
  enableNativeClick: false,
  flowType: 'cart_permalinks',
  cartPageLogin: true,
  recurpayEnabled: false,
  cartSelector: '',
  productSelector: '',
};

function reducer(state = INITIAL_STATE, action) {
  switch (action.type) {
    case 'INITIALISE_DATA': {
      return action.payload;
    }
    case 'UPDATE_FIELD': {
      return { ...state, ...action.payload };
    }

    default: {
      return state;
    }
  }
}

export default reducer;
