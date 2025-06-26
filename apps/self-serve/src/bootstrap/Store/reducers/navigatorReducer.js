import { merchantFetch } from '@libs/web-nexus/merchant/utils/merchantFetch';
import { merge } from '@libs/shared-utils';

const FETCH_TERMINAL_PROVIDERS = 'FETCH_TERMINAL_PROVIDERS';
const FETCH_SUPPORTED_GATEWAYS = 'FETCH_SUPPORTED_GATEWAYS';

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
  supportedGateways_loading: false,
  supportedGateways: [],
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

const getSupportedGateways = () => {
  return merchantFetch({
    url: 'terminals/proxy/optimizer/supported_gateways',
    method: 'get',
  }).then((d) => d.data);
};

export const fetchSupportedGateways = () => {
  return {
    type: FETCH_SUPPORTED_GATEWAYS,
    payload: getSupportedGateways(),
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

    case `${FETCH_SUPPORTED_GATEWAYS}::SUCCESS`:
      return merge(state, {
        supportedGateways_loading: false,
        supportedGateways: action.payload,
      });

    case `${FETCH_SUPPORTED_GATEWAYS}::PENDING`:
      return merge(state, {
        supportedGateways_loading: true,
      });

    case `${FETCH_SUPPORTED_GATEWAYS}::ERROR`:
      return merge(state, {
        supportedGateways_loading: false,
        error: action.payload.errors,
      });

    default:
      return state;
  }
}
