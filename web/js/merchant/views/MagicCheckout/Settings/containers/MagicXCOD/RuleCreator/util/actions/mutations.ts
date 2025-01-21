import type {
  Action,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type AddAction = (rule: Rule, action: Action) => Rule;
export const add: AddAction = (rule, action) => {
  const actions = [...rule.actions, action];

  return { ...rule, actions: [...actions] };
};

type UpdateAction = (rule: Rule, prop: keyof Action, value: any, index: number) => Rule;
export const update: UpdateAction = (rule, prop, value, index) => {
  if (index === undefined || index < 0) {
    throw new Error("Can't update action without `index` (position)");
  }

  const { actions } = rule;
  actions[index][prop] = value;

  return { ...rule, actions: [...actions] };
};

type RemoveAction = (rule: Rule, index: number) => Rule;
export const remove: RemoveAction = (rule, index) => {
  if (index === undefined || index < 0) {
    throw new Error("Can't remove action without `index` (position)");
  }

  const { actions } = rule;
  actions.splice(index, 1);

  return { ...rule, actions: [...actions] };
};
