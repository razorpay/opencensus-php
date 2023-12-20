import { merchantFetch } from 'merchant/utils/ajax';
import { set } from 'common/utils/immutable';

//reducer constant
const FETCH_WORKFLOW_STATUS = 'FETCH_WORKFLOW_STATUS';

const FETCH_BUSINESS_WEBSITE_AUTOMATION_STATUS = 'FETCH_BUSINESS_WEBSITE_AUTOMATION_STATUS';

// initialState for each workflow
const initialState = {
  increase_transaction_limit: {
    loading: true,
    error: null,
  },
  increase_international_transaction_limit: {
    loading: true,
    error: null,
  },
  add_additional_website: {
    loading: true,
    error: null,
  },
  // for add/update business website
  additional_website: {
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
  toggle_international_revamped: {
    loading: true,
    error: null,
  },
  business_website_automation_status: {
    loading: true,
    data: {},
    error: null,
  },
};

export const fetchBusinessWebsiteFeatureStatus = (mid) => {
  return {
    type: FETCH_BUSINESS_WEBSITE_AUTOMATION_STATUS,
    resource: {
      feature: 'website_automated_checks',
    },
    payload: merchantFetch(`feature/merchant/${mid}/website_automated_checks`),
  };
};

//actions
export const fetchWorkflowStatus = (workflowType) => {
  return {
    type: FETCH_WORKFLOW_STATUS,
    payload: merchantFetch({
      url: `merchant/${workflowType}/details`,
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

    case `${FETCH_BUSINESS_WEBSITE_AUTOMATION_STATUS}::SUCCESS`: {
      return set(state, 'business_website_automation_status', {
        loading: false,
        error: null,
        data: action.payload.data,
      });
    }

    case `${FETCH_BUSINESS_WEBSITE_AUTOMATION_STATUS}::ERROR`: {
      return set(state, 'business_website_automation_status', {
        loading: false,
        data: state.business_website_automation_status.data,
        error: action.payload.errors,
      });
    }

    default:
      return state;
  }
};
