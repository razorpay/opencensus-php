import { merge } from 'common/utils/immutable';

import { ACTIONS, type Action } from './actions';

import type { MagicXACODRulesState, Rule } from './types';

const initialState: MagicXACODRulesState = {
  isLoading: {
    rules: true,
    ruleFacts: false,
  },
  rules: [],
  ruleFacts: [],
};

export const magicxACODRulesReducer = (
  state = initialState,
  action: Action,
): MagicXACODRulesState => {
  switch (action.type) {
    // rules (fetch)
    case ACTIONS.FETCH_ACOD_RULES_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, rules: true } });
    case ACTIONS.FETCH_ACOD_RULES_SUCCESS: {
      const { rules = [] } = action.payload?.data || {};
      return merge(state, {
        isLoading: { ...state.isLoading, rules: false },
        rules,
      });
    }
    case ACTIONS.FETCH_ACOD_RULES_ERROR:
      return merge(state, {
        isLoading: { ...state.isLoading, rules: false },
      });

    // rules (operations)
    case ACTIONS.ADD_ACOD_RULE:
      return merge(state, {
        rules: [action.payload, ...state.rules],
      });
    case ACTIONS.UPDATE_ACOD_RULE:
      return merge(state, {
        rules: state.rules.map((rule: Rule) => {
          if (rule.id === action.payload.id) {
            return action.payload;
          }

          return rule;
        }),
      });
    case ACTIONS.REMOVE_ACOD_RULE:
      return merge(state, {
        rules: state.rules.filter((rule: Rule) => rule.id !== action.payload.id),
      });

    // rulefacts (fetch)
    case ACTIONS.FETCH_ACOD_RULEFACTS_PENDING:
      return merge(state, { isLoading: { ...state.isLoading, ruleFacts: true } });
    case ACTIONS.FETCH_ACOD_RULEFACTS_SUCCESS: {
      const { rule_facts: ruleFacts = [] } = action.payload?.data || {};
      return merge(state, {
        ruleFacts,
        isLoading: { ...state.isLoading, ruleFacts: false },
      });
    }
    case ACTIONS.FETCH_ACOD_RULEFACTS_ERROR:
      return merge(state, {
        isLoading: { ...state.isLoading, ruleFacts: false },
      });

    default:
      return state;
  }
};
