// utils
import { merge } from 'common/utils/immutable';

// constants
import { UPLOAD_PAYMENT_INVOICE, UPLOAD_PAYMENT_VIEW_INVOICE } from './constants';

const initialState = {
  isUploading: false,
  error: null,
  invoiceUploading: {},
  invoiceFetching: {},
};

export function paymentUploadInvoiceReducer(state = initialState, action) {
  switch (action.type) {
    case `${UPLOAD_PAYMENT_INVOICE}::PENDING`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceUploading: {
            ...state.invoiceUploading,
            [action.payload.id]: true,
          },
        });
      }
      return state;
    }
    case `${UPLOAD_PAYMENT_INVOICE}::ERROR`:
    case `${UPLOAD_PAYMENT_INVOICE}::SUCCESS`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceUploading: {
            ...state.invoiceUploading,
            [action.payload.id]: false,
          },
        });
      }
      return state;
    }
    case `${UPLOAD_PAYMENT_VIEW_INVOICE}::PENDING`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceFetching: {
            ...state.invoiceFetching,
            [action.payload.id]: true,
          },
        });
      }
      return state;
    }
    case `${UPLOAD_PAYMENT_VIEW_INVOICE}::ERROR`:
    case `${UPLOAD_PAYMENT_VIEW_INVOICE}::SUCCESS`: {
      if (action.payload?.id) {
        return merge(state, {
          invoiceFetching: {
            ...state.invoiceFetching,
            [action.payload.id]: false,
          },
        });
      }
      return state;
    }

    default: {
      return state;
    }
  }
}
