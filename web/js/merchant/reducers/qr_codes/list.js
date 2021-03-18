import { set, merge, unshift, remove } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import { RZPFeatures } from 'merchant/helpers/data';

import { setOnBoardingDataInLocalState } from 'merchant/components/OnBoarding';
import { setQuickGuideIsClosedInLocalStorage } from 'merchant/components/QuickGuide';

import store from 'merchant/store';
export const QR_CODES_FETCH = 'QR_CODES_FETCH';
export const QR_CODE_CREATE = 'QR_CODE_CREATE';

export const fetchQRCodes = (params) => {
  return {
    type: QR_CODES_FETCH,
    // TODO: Update URL to qr_codes
    payload: merchantFetch('virtual_accounts', params).then((resp) => {
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
      // TODO: Update URL to qr_codes
      url: 'virtual_accounts',
      method: 'post',
      data: payload,
    }),
  };
};

let initialState = {
  loading: true,
  items: [],
  count: 0,
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${QR_CODES_FETCH}::PENDING`:
      return merge(state, {
        loading: true,
        items: [],
      });

    case `${QR_CODES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        items: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${QR_CODES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${QR_CODE_CREATE}::SUCCESS`: {
      return merge(state, {
        items: [...state.items, action.payload.data],
      });
    }

    default:
      return state;
  }
}
