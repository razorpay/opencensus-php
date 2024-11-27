import { generateId } from '../id';
import { findByPath, getParentPath } from '../path';
import { isConditionGroup } from './index';

import { Rule, ConditionOrGroup, Path } from '../../types';

const prepareConditionOrGroup = (
  cg: ConditionOrGroup,
  options: { path: Path; idGenerator: () => string },
): ConditionOrGroup => {
  const { idGenerator, path } = options;

  cg.path = path;
  if (!Boolean(cg.id)) {
    cg.id = idGenerator();
  }

  if (isConditionGroup(cg)) {
    cg.combinator = 'and';
  }

  return cg;
};

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
      path: [...parentPath, parent.conditions.length],
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
    parent.conditions.splice(index, 1);
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
