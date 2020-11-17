import InstantSettlement from 'merchant/models/InstantSettlement';
import { set, merge } from 'common/utils/immutable';

const INSTANT_SETTLEMENT_FETCH = 'INSTANT_SETTLEMENT_FETCH';
const INSTANT_SETTLEMENT_TOTAL_SETTLED_AMOUNT_FETCH =
  'INSTANT_SETTLEMENT_TOTAL_SETTLEMENT_AMOUNT_FETCH';

export const fetchItem = (id) => {
  const instantSettlement = new InstantSettlement();
  return {
    type: INSTANT_SETTLEMENT_FETCH,
    payload: instantSettlement.fetch(id, { 'expand[]': 'ondemand_payouts' }),
  };
};

export const fetchTotalSettlementAmount = (id) => {
  const instantSettlement = new InstantSettlement();
  return {
    type: INSTANT_SETTLEMENT_TOTAL_SETTLED_AMOUNT_FETCH,
    payload: instantSettlement.fetch(id),
  };
};

const initialState = {
  loading: true,
  error: null,
  loadingTotalSettledAmount: false,
  instantSettlement: {},
};

export default function (state = initialState, action) {
  switch (action.type) {
    case `${INSTANT_SETTLEMENT_FETCH}::PENDING`:
      return set(state, 'loading', true);
    case `${INSTANT_SETTLEMENT_FETCH}::SUCCESS`:
      return merge(state, {
        loading: false,
        instantSettlement: action.payload,
        error: null,
      });
    case `${INSTANT_SETTLEMENT_FETCH}::ERROR`:
      return merge(state, {
        loading: false,
        error: action.payload.errors,
        instantSettlement: initialState.instantSettlement,
      });
    case `${INSTANT_SETTLEMENT_TOTAL_SETTLED_AMOUNT_FETCH}::PENDING`:
      return set(state, 'loadingTotalSettledAmount', true);
    case `${INSTANT_SETTLEMENT_TOTAL_SETTLED_AMOUNT_FETCH}::SUCCESS`:
      return merge(state, {
        loadingTotalSettledAmount: false,
        instantSettlement: {
          ...state.instantSettlement,
          amount_settled: action.payload.amount_settled,
        },
      });
    case `${INSTANT_SETTLEMENT_TOTAL_SETTLED_AMOUNT_FETCH}::ERROR`:
      return set(state, 'loadingTotalSettledAmount', false);
    default:
      return state;
  }
}
