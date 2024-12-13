import type {
  ConditionOrGroup,
  Path,
  Rule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const findByPath = (path: Path, rule: Rule): ConditionOrGroup | undefined => {
  let level = 0;
  const root = rule.condition;
  let conditionOrGroup: ConditionOrGroup = root;

  while (level < path.length) {
    if ('conditions' in conditionOrGroup) {
      conditionOrGroup = conditionOrGroup.conditions[path[level]];
    } else {
      return undefined;
    }
    level++;
  }

  return conditionOrGroup;
};

export const getParentPath = (path: Path): Path => path.slice(0, -1);
