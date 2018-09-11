import { set, merge, unshift } from 'rzp/utils/immutable';
import Reversal from 'merchant/models/Reversal';
import { makeEntityReducer } from 'rzp/modules/entity';

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
