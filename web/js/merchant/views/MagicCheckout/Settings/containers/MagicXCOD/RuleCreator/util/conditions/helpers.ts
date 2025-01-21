import { isConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/conditions';
import { generateId } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/id';

import type {
  ConditionOrGroup,
  ConditionOrGroupWithoutIds,
  Path,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const prepareConditionOrGroup = (
  cg: ConditionOrGroupWithoutIds,
  options: { path: Path; idGenerator: () => string },
): ConditionOrGroup => {
  const { idGenerator = generateId, path } = options;

  cg.path = path;
  if (!Boolean(cg.id)) {
    cg.id = idGenerator();
  }

  if (isConditionGroup(cg)) {
    cg.combinator = 'and';
  }

  return cg as ConditionOrGroup;
};

export const updatePathsOn = (cg: ConditionOrGroup): ConditionOrGroup => {
  const path = cg.path || [];

  if (isConditionGroup(cg)) {
    cg.conditions.forEach((conditionOrGroup, index) => {
      conditionOrGroup.path = [...path, index];
      updatePathsOn(conditionOrGroup);
    });
  }

  return cg;
};
