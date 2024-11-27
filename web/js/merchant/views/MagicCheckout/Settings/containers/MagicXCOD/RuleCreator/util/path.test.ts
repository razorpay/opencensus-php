import { findByPath, getParentPath } from './path';
import { sampleRule } from '../__tests__/mocks/rules';
import { ConditionGroup } from '../types';

describe('findByPath', () => {
  it('should return the root condition group when path is empty', () => {
    const result = findByPath([], sampleRule);
    expect(result).toEqual(sampleRule.condition);
  });

  it('should return a top-level condition when path points to it', () => {
    const result = findByPath([0], sampleRule);
    expect(result).toEqual(sampleRule.condition.conditions[0]);
  });

  it('should return a nested condition group', () => {
    const result = findByPath([1], sampleRule);
    expect(result).toEqual(sampleRule.condition.conditions[1]);
  });

  it('should return a deeply nested condition', () => {
    const result = findByPath([1, 1], sampleRule);
    expect(result).toEqual((sampleRule.condition.conditions[1] as ConditionGroup).conditions[1]);
  });

  it('should return undefined for an invalid path', () => {
    const result = findByPath([2], sampleRule);
    expect(result).toBeUndefined();
  });

  it('should return undefined for an out-of-bounds path within a nested group', () => {
    const result = findByPath([1, 2], sampleRule);
    expect(result).toBeUndefined();
  });

  it('should return undefined for an invalid nested path', () => {
    const result = findByPath([0, 0], sampleRule);
    expect(result).toBeUndefined();
  });
});

describe('getParentPath', () => {
  it('should return parent condition or group path', () => {
    expect(getParentPath([0, 2, 1])).toEqual([0, 2]);
  });

  it('should return root path when called with root condition', () => {
    expect(getParentPath([])).toEqual([]);
  });
});
