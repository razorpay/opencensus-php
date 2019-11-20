import { set, merge, unshift } from 'common/utils/immutable';
import Reversal from 'merchantLA/models/Reversal';
import { makeEntityReducer } from 'merchant_common/reducers/entity';

const REVERSAL_FETCH = 'REVERSAL_FETCH';

export const fetchReversal = id => {
  const reversal = new Reversal({ id });

  return {
    type: REVERSAL_FETCH,
    payload: reversal.fetch(id, {}),
  };
};

let defaultInitialState = {
  loading: true,
  error: null,
  entity: {},
};

const reversalReducer = makeEntityReducer(REVERSAL_FETCH, defaultInitialState);

export default reversalReducer;
