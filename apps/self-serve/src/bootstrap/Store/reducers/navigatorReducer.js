import { merchantFetch } from '@dashboard/shared-utils/ajax';
import { merge } from '@dashboard/shared-utils/immutable';

const FETCH_TERMINAL_PROVIDERS = 'FETCH_TERMINAL_PROVIDERS';

const initialState = {
  loading: true,
  rules: [],
  rules_loaded: false,
  create_rule_loading: false,
  rule_detail_loading: false,
  reorder_loading: false,
  deactivate_loading: false,
  default_rule: {
    name: 'DefaultRule',
    description: 'DefaultRuleDesc',
    precondition: {},
    rules: [],
  },
  providers_loading: false,
  terminalProviders: [],
  error: null,
};

export const getTerminalProviders = () => {
  const params = {
    url: 'terminals/proxy/optimizer/list/mid/provider',
    method: 'get',
  };
  return merchantFetch(params).then((d) => d.data);
};

export const fetchTerminalProviders = () => {
  return {
    type: FETCH_TERMINAL_PROVIDERS,
    payload: getTerminalProviders(),
  };
};

export default function navigatorReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_TERMINAL_PROVIDERS}::SUCCESS`:
      return merge(state, {
        providers_loading: false,
        terminalProviders: action.payload,
        error: null,
      });

    case `${FETCH_TERMINAL_PROVIDERS}::PENDING`:
      return merge(state, {
        providers_loading: true,
      });

    case `${FETCH_TERMINAL_PROVIDERS}::ERROR`:
      return merge(state, {
        providers_loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
