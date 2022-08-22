import { merchantFetch } from 'merchant/utils/ajax';
import { paiseToRupees } from 'common/utils/rzp-utils';
import { set, merge } from 'common/utils/immutable';

const FETCH_TRANSACTION = 'FETCH_TRANSACTION';
const TIMEOUT_ANALYTICS_API = 6000;

export const fetchAmount = (activatedAt) => {
  return {
    type: FETCH_TRANSACTION,
    payload: merchantFetch({
      url: 'merchant/analytics',
      method: 'post',
      timeout: TIMEOUT_ANALYTICS_API,
      data: {
        filters: {
          default: [
            {
              created_at: { gte: activatedAt, lte: new Date().getTime() },
              authorized_at: { gt: 0 },
            },
          ],
        },
        aggregations: {
          firstTransaction: {
            agg_type: 'oldest',
            details: {
              index: 'payments',
              column: 'created_at',
              mode: 'live',
              limit: 1,
              result_fields: ['base_amount'],
            },
          },
        },
      },
    }),
  };
};

const initialState = {
  amount: null,
  amountWhenAPITimeout: 0,
};

const updateAmount = (state, response) => {
  const payment = response?.data?.firstTransaction?.result?.length
    ? paiseToRupees(response.data.firstTransaction.result[0].base_amount)
    : 0;
  return merge(state, { amount: payment });
};

/*eslint func-names: ["error", "never"]*/
export default function (state = initialState, action) {
  switch (action.type) {
    case `${FETCH_TRANSACTION}::PENDING`:
      return set(state, 'amount', initialState.amount);

    case `${FETCH_TRANSACTION}::SUCCESS`:
      return updateAmount(state, action.payload);

    case `${FETCH_TRANSACTION}::ERROR`:
      if (action.payload && action.payload.code === 'ECONNABORTED') {
        return set(state, 'amount', initialState.amountWhenAPITimeout);
      } else {
        return set(state, 'amount', initialState.amount);
      }

    default:
      return state;
  }
}
