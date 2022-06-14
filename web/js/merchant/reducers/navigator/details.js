import { merge } from 'common/utils/immutable';
import { merchantFetch } from 'merchant/utils/ajax';
import {
  rule,
  DEFAULT_RULE,
  getRuleScore,
  appendMid,
} from 'merchant/views/Navigator/components/util';

const FETCH_RULE = 'FETCH_RULE';
const FETCH_RULES = 'FETCH_RULES';
const REORDER_RULES = 'REORDER_RULES';
const RULE_RESET = 'RULE_RESET';
const FETCH_TERMINAL_PROVIDERS = 'FETCH_TERMINAL_PROVIDERS';
const CHANGE_RULE_MODE = 'CHANGE_RULE_MODE';
const DELETE_RULE = 'DELETE_RULE';
const CREATE_RULE = 'CREATE_RULE';
const UPDATE_RULE = 'UPDATE_RULE';
export const getRule = (id) => {
  const params = {
    url: `merchant/mid/rule_groups/${id}`,
    method: 'get',
  };
  return merchantFetch(params).then((d) => {
    const rule = d.data;
    if (rule.name === appendMid(DEFAULT_RULE)) {
      rule.is_default = true;
    }
    rule.score = getRuleScore(rule);
    return rule;
  });
};

export const createRuleGroup = (data) => {
  const params = {
    url: 'merchant/mid/rule_groups',
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    data,
  };
  return merchantFetch(params).then((d) => d.data);
};

export const updateRuleGroup = (data) => {
  const id = data.id;
  // delete data.id;
  const params = {
    url: `merchant/mid/rule_groups/${id}`,
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    data,
  };
  return merchantFetch(params).then((d) => d.data);
};

export const getRules = () => {
  const params = {
    url: `merchant/mid/rule_groups`,
    method: 'get',
  };
  return merchantFetch(params).then((a) => {
    const rules = a.data.filter((a) => a.rules);
    rules.sort((a, b) => a.rules[0].score - b.rules[0].score);
    return rules;
  });
};

export const reorderRuleGroups = (rules, rule) => {
  const body = {
    order: {
      ordered_names: rules.map((r) => r.name),
    },
  };
  if (rule) {
    body.rule_group_id = rule.id;
  }
  const params = {
    url: 'merchant/mid/reorder_rule_groups',
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
    },
    data: body,
  };
  return merchantFetch(params).then(() => getRules());
};

export const getTerminalProviders = () => {
  const params = {
    url: 'terminals/proxy/optimizer/list/mid/provider',
    method: 'get',
  };
  return merchantFetch(params).then((d) => d.data);
};

export const deleteRuleGroup = (id) => {
  const params = {
    url: `merchant/mid/rule_groups/${id}`,
    method: 'delete',
  };
  return merchantFetch(params).then((d) => d.data);
};

export const updateRuleMode = (id, mode) => {
  const params = {
    url: `merchant/mid/rule_groups/${id}/mode/${mode}`,
    method: 'put',
  };
  return merchantFetch(params).then(() => {
    return getRule(id);
  });
};

export const fetchRule = (id) => {
  return {
    type: FETCH_RULE,
    payload: getRule(id),
  };
};

export const fetchRules = () => {
  return {
    type: FETCH_RULES,
    payload: getRules(),
  };
};

export const changeRuleMode = (id, mode) => {
  return {
    type: CHANGE_RULE_MODE,
    payload: updateRuleMode(id, mode),
  };
};

export const createRule = (id, mode) => {
  return {
    type: CREATE_RULE,
    payload: createRuleGroup(id, mode),
  };
};

export const updateRule = (rule) => {
  return {
    type: UPDATE_RULE,
    payload: updateRuleGroup(rule),
  };
};

export const reorderRules = (rules, rule) => {
  return {
    type: REORDER_RULES,
    payload: reorderRuleGroups(rules, rule),
  };
};

export const fetchTerminalProviders = () => {
  return {
    type: FETCH_TERMINAL_PROVIDERS,
    payload: getTerminalProviders(),
  };
};

export const deleteRule = (id) => {
  return {
    type: DELETE_RULE,
    payload: deleteRuleGroup(id),
  };
};

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
  rule,
  error: null,
};

export default function navigatorReducer(state = initialState, action) {
  switch (action.type) {
    case `${FETCH_RULE}::SUCCESS`:
      if (action.payload.name === appendMid(DEFAULT_RULE)) {
        action.payload.is_default = true;
      }
      return merge(state, {
        rule_detail_loading: false,
        rule: action.payload,
        error: null,
      });

    case `${FETCH_RULE}::ERROR`:
      return merge(state, {
        rule_detail_loading: false,
        error: action.payload.errors,
      });

    case `${FETCH_RULE}::PENDING`:
      return merge(state, {
        rule_detail_loading: true,
      });

    case `${FETCH_RULES}::PENDING`:
      return merge(state, {
        loading: true,
        deactivate_loading: true,
        rule_detail_loading: true,
      });

    case `${FETCH_RULES}::SUCCESS`: {
      let dr;
      const rules = action.payload;
      rules.forEach((r, i) => {
        if (r.name === appendMid(DEFAULT_RULE)) {
          dr = r;
          rules.splice(i, 1);
        }
      });
      const body = {
        loading: false,
        rules_loaded: true,
        rule_detail_loading: false,
        deactivate_loading: false,
        rules,
        error: null,
      };
      if (dr) {
        body.default_rule = dr;
      }
      return merge(state, body);
    }

    case `${CHANGE_RULE_MODE}::ERROR`:
      return merge(state, {
        rule_detail_loading: false,
        deactivate_loading: false,
        create_rule_loading: false,
        // create_rule_loading: false,
        error: action.payload.errors,
      });

    case `${CHANGE_RULE_MODE}::PENDING`:
      return merge(state, {
        create_rule_loading: true,
        rule_detail_loading: true,
        deactivate_loading: true,
      });

    case `${CHANGE_RULE_MODE}::SUCCESS`: {
      const RULES = state.rules;
      const ruleIndex = state.rules.findIndex((rule) => rule.id === action.payload.id);
      const BDY = {
        rule_detail_loading: false,
        deactivate_loading: false,
        create_rule_loading: false,
        rules: RULES,
        error: action.payload.errors,
      };
      if (state.rule.id == action.payload.id) {
        BDY.rules[ruleIndex] = action.payload;
        BDY.rule = action.payload;
      }

      return merge(state, BDY);
    }

    case `${REORDER_RULES}::ERROR`:
      return merge(state, {
        reorder_loading: false,
        error: action.payload.errors,
      });

    case `${REORDER_RULES}::PENDING`:
      return merge(state, {
        reorder_loading: true,
      });

    case `${REORDER_RULES}::SUCCESS`: {
      let RULES = state.rules;
      const BODY = {
        reorder_loading: false,
      };

      if (action.payload.length) {
        RULES = action.payload;
        BODY.rules = RULES;
        const RULE = RULES.find((rule) => rule.id === state.rule.id);
        if (RULE) {
          BODY.rule = RULE;
        }
        const dri = BODY.rules.findIndex((rule) => rule.name === appendMid(DEFAULT_RULE));
        if (dri !== -1) {
          BODY.default_rule = BODY.rules[dri];
          BODY.rules.splice(dri, 1);
        }
      }
      return merge(state, BODY);
    }

    case `${FETCH_RULES}::ERROR`:
      return merge(state, {
        loading: false,
        deactivate_loading: false,
        error: action.payload.errors,
      });

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

    case `${DELETE_RULE}::SUCCESS`:
      return merge(state, {
        rule_detail_loading: false,
        rules: state.rules.filter((r) => r.id !== state.rule.id),
        error: null,
      });

    case `${DELETE_RULE}::ERROR`:
      return merge(state, {
        rule_detail_loading: false,
        error: action.payload.errors,
      });

    case `${DELETE_RULE}::PENDING`:
      return merge(state, {
        rule_detail_loading: true,
      });

    case `${CREATE_RULE}::ERROR`:
      return merge(state, {
        rule_detail_loading: false,
        create_rule_loading: false,
        error: action.payload.errors,
      });

    case `${CREATE_RULE}::SUCCESS`:
      return merge(state, {
        create_rule_loading: false,
        rule: action.payload,
        rules: [...state.rules, action.payload],
        error: null,
      });

    case `${CREATE_RULE}::PENDING`:
      return merge(state, {
        create_rule_loading: true,
      });

    case `${UPDATE_RULE}::ERROR`:
      return merge(state, {
        rule_detail_loading: false,
        create_rule_loading: false,
        error: action.payload.errors,
      });

    case `${UPDATE_RULE}::SUCCESS`: {
      let DR;
      const RU = state.rules.map((r) => {
        if (r.name === appendMid(DEFAULT_RULE)) {
          DR = r;
        }
        if (r.id == action.payload.id) {
          return action.payload;
        } else {
          return r;
        }
      });
      if (action.payload.name === appendMid(DEFAULT_RULE)) {
        DR = action.payload;
      }
      const BD = {
        rules: RU,
        create_rule_loading: false,
        error: action.payload.errors,
      };
      if (DR) {
        BD.default_rule = DR;
      }
      return merge(state, BD);
    }

    case `${UPDATE_RULE}::PENDING`:
      return merge(state, {
        create_rule_loading: true,
      });

    case `${RULE_RESET}`:
      return initialState;

    default:
      return state;
  }
}
