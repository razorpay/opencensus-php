import { set, merge, unshift } from 'common/utils/immutable';
import Tax from 'merchant/models/Tax';

export const TAXES_FETCH = 'TAXES_FETCH';
export const TAX_CREATE = 'TAX_CREATE';
const GST_TAX_FETCH = 'GST_TAX_FETCH';

/**
 * Fetches all the taxes.
 * @param {Object} params
 * @return {Object}
 */
export const fetchTaxes = params => {
  let tax = new Tax();
  return {
    type: TAXES_FETCH,
    payload: tax.fetchAll(params),
  };
};

/**
 * Saves a tax.
 * @param {Object} params
 * @return {Object}
 */
export const saveTax = params => {
  let tax = new Tax(params);
  return {
    type: TAX_CREATE,
    payload: tax.save(),
  };
};

/**
 * Fetches GST Taxes
 * @return {Object}
 */
export const fetchGSTTaxes = () => {
  return {
    type: GST_TAX_FETCH,
    payload: Tax.fetchGSTTaxes(),
  };
};

let initialState = {
  loading: true,
  taxes: [],
  count: 0,
};

export default function(state = initialState, action) {
  switch (action.type) {
    case `${TAXES_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${TAXES_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        taxes: action.payload.data.items,
        count: action.payload.data.count,
      });

    case `${TAXES_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    case `${TAX_CREATE}::SUCCESS`:
      return set(state, 'taxes', unshift(state.taxes, action.payload));

    case `${GST_TAX_FETCH}::PENDING`:
      return set(state, 'loading', true);

    case `${GST_TAX_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        gst_taxes: action.payload.data,
      });

    case `${GST_TAX_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.error,
      });

    default:
      return state;
  }
}
