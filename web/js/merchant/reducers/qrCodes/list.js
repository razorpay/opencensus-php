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
