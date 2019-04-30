import Commission from 'merchant/models/Commission';

import { makeEntityReducer } from 'rzp/modules/entity';

const COMMISSION_FETCH = 'COMMISSION_FETCH';
const COMMISSION_AGGREGATE_FETCH = 'COMMISSION_AGGREGATE_FETCH';
const COMM_AGG_SINGLE_DAY_FETCH = 'COMM_AGG_SINGLE_DAY_FETCH';

export const fetchCommission = id => ({
  type: COMMISSION_FETCH,
  payload: new Commission().fetch(id),
});

export default makeEntityReducer(COMMISSION_FETCH);

export const fetchAggregate = params => ({
  type: COMMISSION_AGGREGATE_FETCH,
  payload: new Commission().fetchDailyAggregateData(params),
});

export const commAggSingleDayReducer = makeEntityReducer(
  COMM_AGG_SINGLE_DAY_FETCH
);

export const fetchSingleDayAggregate = (from, mode) => {
  return {
    type: COMM_AGG_SINGLE_DAY_FETCH,
    payload: new Commission().fetchSingleDayAggregateData({ from, mode }),
  };
};
