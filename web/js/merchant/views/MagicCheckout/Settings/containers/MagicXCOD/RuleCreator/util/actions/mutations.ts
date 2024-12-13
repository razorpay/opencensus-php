import type {
  Action,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type AddAction = (rule: Rule, action: Action) => Rule;
export const add: AddAction = (rule, action) => {
  const actions = [...rule.actions, action];

  return { ...rule, actions: [...actions] };
};

type UpdateAction = (rule: Rule, prop: keyof Action, value: any, index?: number) => Rule;
export const update: UpdateAction = (rule, prop, value, index = 0) => {
  const { actions } = rule;
  actions[index][prop] = value;

  return { ...rule, actions: [...actions] };
};
