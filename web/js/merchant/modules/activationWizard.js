import { merchantFetch } from 'merchant/utils/ajax';
import Activation from 'merchant/models/Activation';
import { set, merge, push } from 'rzp/utils/immutable';
// import {dispatch} from 'redux';

const initialState = {
  loading: false,
  data: {},
  error: null,
};

const SUBMIT_L1_FORM = 'SUBMIT_L1_FORM';
const SUBMIT_L1_FORM_SUCESS = `${SUBMIT_L1_FORM}::SUCCESS`;

export const submitL1Form = ({ data, accountId = ' ' }) => {
  let activation = new Activation({ ...data, accountId });
  // return {
  //     type: SUBMIT_L1_FORM,
  //     payload: activation.submitL1Form(data)
  // };
  return () => activation.submitL1Form(data);
};

export const submitL1FormSuccess = ({ data }) => ({
  type: SUBMIT_L1_FORM_SUCESS,
  payload: data,
});

export default function(state = initialState, action) {
  switch (action.type) {
    case `${SUBMIT_L1_FORM}::SUCCESS`:
      console.log('success submitting l1 form...........', action.payload);
      return merge(state, {
        data: action.payload,
      });

    default:
      return state;
  }
}
