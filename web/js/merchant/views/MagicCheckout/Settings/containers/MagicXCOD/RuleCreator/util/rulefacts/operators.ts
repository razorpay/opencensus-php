import type {
  Fact,
  Operator,
  OperatorValue,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

export const operators: Record<OperatorValue, Operator> = {
  eq: { name: 'eq', label: 'equals', value: 'eq' },
  lt: { name: 'lt', label: 'less than', value: 'lt' },
  le: { name: 'le', label: 'less than or equal to', value: 'le' },
  gt: { name: 'gt', label: 'greater than', value: 'gt' },
  ge: { name: 'ge', label: 'greater than or equal to', value: 'ge' },
  in: { name: 'in', label: 'in', value: 'in' },
};

const factTypeToOperatorMap = {
  boolean: [operators.eq],
  string: [operators.in],
  number: [operators.eq, operators.lt, operators.le, operators.gt, operators.ge],
};

export const getOperatorsForFactType = (factType: Fact['type']): NonNullable<Fact['operators']> => {
  return factTypeToOperatorMap[factType];
};
