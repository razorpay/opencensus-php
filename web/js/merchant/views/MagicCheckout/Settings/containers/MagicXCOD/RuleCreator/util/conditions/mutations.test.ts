import { add, remove, update } from './mutations';
import { generateId } from '../id';

import { sampleRule as mockedRule } from '../../__tests__/mocks/rules';

import type { Condition, ConditionGroup, Rule } from '../../types';

let sampleRule: Rule;

beforeEach(() => {
  sampleRule = JSON.parse(JSON.stringify(mockedRule));
});

describe('add', () => {
  it('adds a new condition to the root condition group', () => {
    const newCondition: Condition = {
      fact: 'price',
      operator: 'gt',
      value: 100,
    };
    const updatedRule = add(sampleRule, newCondition, [], {
      idGenerator: generateId,
    });

    const rootGroup = updatedRule.condition;
    expect(rootGroup.conditions.length).toBe(3);
    expect(rootGroup.conditions[2]).toMatchObject({
      fact: 'price',
      operator: 'gt',
      value: 100,
      path: [2],
    });
  });

  it('adds a new condition to a nested condition group', () => {
    const newCondition: Condition = {
      fact: 'category',
      operator: 'eq',
      value: 'clothing',
    };
    const updatedRule = add(sampleRule, newCondition, [1], {
      idGenerator: generateId,
    });

    const nestedGroup = updatedRule.condition.conditions[1] as ConditionGroup;
    expect(nestedGroup.conditions.length).toBe(3);
    expect(nestedGroup.conditions[2]).toMatchObject({
      fact: 'category',
      operator: 'eq',
      value: 'clothing',
      path: [1, 2],
    });
  });

  it('returns the same rule if the parent path is not a condition group', () => {
    const newCondition: Condition = {
      fact: 'rating',
      operator: 'lt',
      value: 3,
    };
    const updatedRule = add(sampleRule, newCondition, [0], {
      idGenerator: generateId,
    });
    expect(updatedRule).toEqual(sampleRule);
  });
});

describe('remove', () => {
  it('removes a condition by path', () => {
    const updatedRule = remove(sampleRule, [1, 1]);

    const nestedGroup = updatedRule.condition.conditions[1] as ConditionGroup;
    expect(nestedGroup.conditions.length).toBe(1);
    expect(nestedGroup.conditions[0].id).toBe('cond2');
  });

  it('removes a nested condition group by path', () => {
    const updatedRule = remove(sampleRule, [1]);

    const rootGroup = updatedRule.condition;
    expect(rootGroup.conditions.length).toBe(1);
    expect(rootGroup.conditions[0].id).toBe('cond1');
  });

  it('returns the same rule if the path is invalid', () => {
    const updatedRule = remove(sampleRule, [5, 0]);
    expect(updatedRule).toEqual(sampleRule);
  });
});

describe('update', () => {
  it('updates a condition property', () => {
    const updatedRule = update(sampleRule, 'value', 'leather', [0]);

    const updatedCondition = updatedRule.condition.conditions[0] as Condition;
    expect(updatedCondition.value).toBe('leather');
  });

  it('updates a nested condition group combinator', () => {
    const updatedRule = update(sampleRule, 'combinator', 'and', [1]);

    const updatedGroup = updatedRule.condition.conditions[1] as ConditionGroup;
    expect(updatedGroup.combinator).toBe('and');
  });

  it('does not update an invalid property', () => {
    const updatedRule = update(sampleRule, 'id', 'new_id', [0]);

    const condition = updatedRule.condition.conditions[0] as Condition;
    expect(condition.id).toBe('cond1');
  });
});
