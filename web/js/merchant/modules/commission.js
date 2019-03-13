import Commission from 'merchant/models/Commission';

import { makeEntityReducer } from 'rzp/modules/entity';

const COMMISSION_FETCH = 'COMMISSION_FETCH';

export const fetchCommission = id => ({
  type: COMMISSION_FETCH,
  payload: new Commission().fetch(id),
});

export default makeEntityReducer(COMMISSION_FETCH);
