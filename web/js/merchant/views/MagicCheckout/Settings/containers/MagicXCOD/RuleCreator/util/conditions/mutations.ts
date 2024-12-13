import { isConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/conditions';
import {
  prepareConditionOrGroup,
  updatePathsOn,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/conditions/helpers';
import { generateId } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/id';
import {
  findByPath,
  getParentPath,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/util/path';

import type {
  Rule,
  ConditionOrGroup,
  Path,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

type AddConditionOrGroup = (
  rule: Rule,
  conditionOrGroup: ConditionOrGroup,
  parentPath: Path,
  options?: {
    idGenerator?: () => string;
  },
) => Rule;
export const add: AddConditionOrGroup = (
  rule,
  conditionOrGroup,
  parentPath,
  { idGenerator = generateId } = {},
) => {
  const parent = findByPath(parentPath, rule);
  if (!parent || !isConditionGroup(parent)) {
    return rule;
  }

  parent.conditions.push(
    prepareConditionOrGroup(conditionOrGroup, {
      idGenerator,
      path: conditionOrGroup.path ?? [...parentPath, parent.conditions.length],
    }),
  );
  return { ...rule };
};

type RemoveConditionOrGroup = (rule: Rule, path: Path) => Rule;
export const remove: RemoveConditionOrGroup = (rule, path) => {
  if (path.length < 1) {
    return rule;
  }

  const index = path[path.length - 1];
  const parentPath = getParentPath(path);
  const parent = findByPath(parentPath, rule);

  if (parent && isConditionGroup(parent)) {
    // remove the condition/group
    const [, ...rest] = parent.conditions.splice(index);

    // update `path` for subsequent entities
    rest.forEach((conditionOrGroup: any) => {
      const pathLength = conditionOrGroup.path?.length ?? 0;
      if (pathLength > 0) {
        const idx = conditionOrGroup.path[pathLength - 1];
        conditionOrGroup.path[pathLength - 1] = idx - 1;

        updatePathsOn(conditionOrGroup);
      }
    });
    parent.conditions = [...parent.conditions, ...rest];
  }

  return { ...rule };
};

type UpdateConditionOrGroup = (rule: Rule, prop: string, value: any, path: Path) => Rule;
export const update: UpdateConditionOrGroup = (rule, prop, value, path) => {
  const conditionOrGroup = findByPath(path, rule);

  if (!conditionOrGroup) {
    return rule;
  }

  const isGroup = isConditionGroup(conditionOrGroup);

  if (prop in conditionOrGroup && prop !== 'id' && prop !== 'path') {
    if (isGroup && prop === 'combinator') {
      conditionOrGroup[prop] = value;
    } else if (!isGroup && (prop === 'fact' || prop === 'operator' || prop === 'value')) {
      conditionOrGroup[prop] = value;
    }
  }

  return { ...rule };
};
