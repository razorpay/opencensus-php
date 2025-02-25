import { set } from '@libs/shared-utils';
import { NewAuthAction, SET_MERCHANT_ID } from './actions';

interface State {
  merchantID?: string;
}

const initialState: State = {};

export default function newAuthReducer(state = initialState, action: NewAuthAction): State {
  switch (action.type) {
    case SET_MERCHANT_ID:
      return set(state, 'merchantID', action.payload);

    default:
      return state;
  }
}
