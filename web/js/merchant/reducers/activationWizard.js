import Activation from 'merchant/models/Activation';
import { merge } from 'common/utils/immutable';

const initialState = {
  loading: false,
  data: {},
  error: null,
  current_tab_name: 'Contact Info',
};

const SUBMIT_L1_FORM = 'SUBMIT_L1_FORM';
const SUBMIT_L1_FORM_SUCESS = `${SUBMIT_L1_FORM}::SUCCESS`;
const SET_CURRENT_TAB = 'ACTIVATION::SET_CURRENT_TAB';

export const submitL1Form = ({ data, accountId = ' ' }) => {
  let activation = new Activation({ ...data, accountId });
  return () => activation.submitL1Form(data);
};

export const submitL1FormSuccess = ({ data }) => ({
  type: SUBMIT_L1_FORM_SUCESS,
  payload: data,
});

export const setCurrentTab = ({ tab_name }) => ({
  type: SET_CURRENT_TAB,
  payload: tab_name,
});

export default function (state = initialState, action) {
  switch (action.type) {
    case `${SUBMIT_L1_FORM}::SUCCESS`:
      return merge(state, {
        data: action.payload,
      });

    case SET_CURRENT_TAB:
      return merge(state, {
        current_tab_name: action.payload,
      });

    default:
      return state;
  }
}
