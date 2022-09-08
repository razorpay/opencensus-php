import { merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { makeActionCollectionReducer, fetchAll } from 'merchant/reducers/collection';
import { RZPFeatures } from 'merchant/helpers/data';
import QRCode from 'merchant/models/QRCode';
import { decodeSensitiveFields } from 'common/utils/rzp-utils';
import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

export const QR_CODE_CREATE = 'QR_CODE_CREATE';
export const QR_CODE_UPDATE = 'QR_CODE_UPDATE';
export const UPI_QR_CODE_CREATE = 'UPI_QR_CODE_CREATE';

export const fetchQRCodes = (params) => {
  const { type, payload } = fetchAll(decodeSensitiveFields(params), QRCode, 'QR_CODES');
  return {
    type,
    payload: payload.then((resp) => {
      if (resp.data.items.length) {
        setOnBoardingDataInLocalState({
          feature: RZPFeatures.QR_CODES,
          data: {
            isEnabled: true,
          },
        });

        if (resp.data.items.length > 2) {
          setQuickGuideIsClosedInLocalStorage(RZPFeatures.QR_CODES, true);
        }
      }

      return resp;
    }),
  };
};

export const saveQRCode = (payload) => {
  return {
    type: QR_CODE_CREATE,
    payload: merchantFetch({
      url: 'payments/qr_codes',
      method: 'post',
      data: payload,
    }),
  };
};

export const saveUPIQRCode = (payload) => {
  return {
    type: UPI_QR_CODE_CREATE,
    payload: merchantFetch({
      url: 'virtual_accounts',
      method: 'post',
      data: payload,
    }),
  };
};

export const closeQR = (id) => {
  return {
    type: QR_CODE_UPDATE,
    payload: merchantFetch({
      url: `payments/qr_codes/${id}/close`,
      method: 'post',
    }),
  };
};

export default makeActionCollectionReducer('QR_CODES', {
  [`${QR_CODE_CREATE}::SUCCESS`]: (state, action) => {
    return merge(state, {
      ...state,
      items: [action.payload.data, ...state.items],
    });
  },

  [`${UPI_QR_CODE_CREATE}::SUCCESS`]: (state, action) => {
    const newItem = { ...action.payload.data };
    const receiver = newItem?.receivers?.[0];
    const requiredData = {
      image_url: receiver?.short_url,
      payment_amount: newItem?.amount_expected,
      payments_amount_received: newItem?.amount_paid,
      type: receiver?.type || 'upi_qr',
      usage: receiver?.usage_type,
      entity: receiver?.entity,
      id: receiver?.id,
    };
    action.payload.data = { ...newItem, ...requiredData };
    return merge(state, {
      ...state,
      items: [action.payload.data, ...state.items],
    });
  },

  [`${QR_CODE_UPDATE}::SUCCESS`]: (state, action) => {
    return merge(state, {
      ...state,
      items: state.items.map((item) => {
        if (item.id === action.payload.data.id) {
          return action.payload.data;
        }

        return item;
      }),
    });
  },
});
