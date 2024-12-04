import { merchantFetch } from 'merchant/utils/ajax';
import type { Rule } from './types';

const REDUCER_NAMESPACE = 'MAGICX_ACOD';

type ActionsType = typeof ACTIONS;
export type Action = {
  type: ActionsType[keyof ActionsType];
  [key: string]: any;
};

export const ACTIONS = {
  FETCH_ACOD_RULES: `${REDUCER_NAMESPACE}_RULES_FETCH`,
  FETCH_ACOD_RULES_PENDING: `${REDUCER_NAMESPACE}_RULES_FETCH::PENDING`,
  FETCH_ACOD_RULES_SUCCESS: `${REDUCER_NAMESPACE}_RULES_FETCH::SUCCESS`,
  FETCH_ACOD_RULES_ERROR: `${REDUCER_NAMESPACE}_RULES_FETCH::ERROR`,

  FETCH_ACOD_RULEFACTS: `${REDUCER_NAMESPACE}_RULEFACTS_FETCH`,
  FETCH_ACOD_RULEFACTS_PENDING: `${REDUCER_NAMESPACE}_FETCH::PENDING`,
  FETCH_ACOD_RULEFACTS_SUCCESS: `${REDUCER_NAMESPACE}_FETCH::SUCCESS`,
  FETCH_ACOD_RULEFACTS_ERROR: `${REDUCER_NAMESPACE}_FETCH::ERROR`,

  ADD_ACOD_RULE: `${REDUCER_NAMESPACE}_RULE_ADD`,
  UPDATE_ACOD_RULE: `${REDUCER_NAMESPACE}_RULE_UPDATE`,
  REMOVE_ACOD_RULE: `${REDUCER_NAMESPACE}_RULE_REMOVE`,
} as const;

export const fetchACODRules = () => {
  return {
    type: ACTIONS.FETCH_ACOD_RULES,
    payload: merchantFetch({
      url: 'magic/sopc/customisations/rules',
    }),
  };
};

export const fetchACODRuleFacts = () => {
  return {
    type: ACTIONS.FETCH_ACOD_RULEFACTS,
    payload: merchantFetch({
      url: 'magic/sopc/customisations/rules/facts',
    }),
  };
};

export const addACODRule = (rule: Rule) => {
  return {
    type: ACTIONS.ADD_ACOD_RULE,
    payload: rule,
  };
};
export const updateACODRule = (rule: Rule) => {
  return {
    type: ACTIONS.UPDATE_ACOD_RULE,
    payload: rule,
  };
};
export const removeACODRule = (rule: Rule) => {
  return {
    type: ACTIONS.REMOVE_ACOD_RULE,
    payload: rule,
  };
};
