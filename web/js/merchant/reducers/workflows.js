import { merchantFetch } from 'merchant/utils/ajax';
import { set } from 'common/utils/immutable';

//reducer constant
const FETCH_WORKFLOW_STATUS = 'FETCH_WORKFLOW_STATUS';

// initialState for each workflow
const initialState = {
  increase_transaction_limit: {
    loading: true,
    error: null,
  },
  add_additional_website: {
    loading: true,
    error: null,
  },
  update_business_website: {
    loading: true,
    error: null,
  },
  bank_detail_update: {
    loading: true,
    error: null,
  },
  gstin_update_self_serve: {
    loading: true,
    error: null,
  },
};

/**
 * Maps `WorflowType` -> workflow status API URL
 */
const workflowURLs = {
  increase_transaction_limit: 'merchant/increase_transaction_limit/details',
  bank_detail_update: 'merchant/bank_detail_update/details',
  add_additional_website: 'merchant/add_additional_website/details',
  update_business_website: 'merchant/additional_website/details',
  gstin_update_self_serve: 'merchant/gstin_update_self_serve/details',
};

//actions
export const fetchWorkflowStatus = (workflowType) => {
  return {
    type: FETCH_WORKFLOW_STATUS,
    payload: merchantFetch({
      url: workflowURLs[workflowType],
      method: 'GET',
      mode: 'live',
      headers: {
        'Content-Type': 'application/json',
      },
    }).then((res) => {
      res.workflowType = workflowType;
      return res;
    }),
  };
};

//reducers
export default (state = initialState, action) => {
  switch (action.type) {
    case `${FETCH_WORKFLOW_STATUS}::SUCCESS`:
      return set(state, action.payload.workflowType, {
        ...action.payload.data,
        loading: false,
        error: null,
      });

    case `${FETCH_WORKFLOW_STATUS}::ERROR`:
      return set(state, action.payload.workflowType, {
        ...state[action.payload.workflowType],
        loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
};
