import React from 'react';
import { screen, userEvent } from 'test-utils';

import { ConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionGroup';
import {
  emptyRule,
  emptyRuleWithTwoConditions,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/conditions';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { RuleCreatorEngine } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';
import { useRuleValidation } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import {
  byteLength,
  getUsagePercentage,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size';
import { lazyRenderACODComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers/acod';

const mockAddCondition = jest.fn();
jest.mock('merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size', () => ({
  byteLength: jest.fn(),
  getUsagePercentage: jest.fn(),
}));
jest.mock('merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks', () => ({
  ...(jest.requireActual(
    'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks',
  ) as object),
  useRuleMutations: jest.fn(() => ({
    conditions: {
      add: mockAddCondition,
    },
  })),
  useRuleValidation: jest.fn(),
}));

const getPropsFromEmptyRule = (rule: any = emptyRule) => {
  const group = rule.condition.conditions[0] as any;
  const condition = group.conditions[0];
  const totalConditionGroups = 1;
  const indexPosition = 0;

  return {
    group,
    condition,
    totalConditionGroups,
    indexPosition,
  };
};

const ConditionGroupComponent = (...props: any) => {
  return (
    <RuleCreatorEngine
      defaultRule={emptyRule}
      sizeLimit="5kb"
      validator={() => ({ conditions: {}, actions: {} })}
      facts={Facts}
    >
      <ConditionGroup {...props[0]} />
    </RuleCreatorEngine>
  );
};
const renderWithProps = lazyRenderACODComponent(ConditionGroupComponent);

describe('<ConditionGroup />', () => {
  beforeEach(() => {
    (byteLength as jest.Mock).mockReturnValue(10);
    (getUsagePercentage as jest.Mock).mockReturnValue(0);
    (useRuleValidation as jest.Mock).mockReturnValue({
      validationResult: {
        conditions: {},
        actions: {},
      },
    });
  });

  afterEach(() => {
    jest.clearAllMocks();
  });

  it('should render', () => {
    const props = getPropsFromEmptyRule();
    renderWithProps(props);

    const conditionGroup = screen.getByTestId('rcm-conditiongroup');
    const condition = screen.getByTestId('rcm-condition');

    expect(conditionGroup).toBeInTheDocument();
    expect(condition).toBeInTheDocument();
  });

  it('should not render combinator selector if only single condition in group', () => {
    const props = getPropsFromEmptyRule();
    renderWithProps(props);

    const combinatorSelect = screen.queryByRole('combobox', { name: /pick group combinator/i });
    expect(combinatorSelect).not.toBeInTheDocument();
  });

  it('should render combinator selector if >1 condition in group', () => {
    const props = getPropsFromEmptyRule(emptyRuleWithTwoConditions);
    renderWithProps(props);

    const combinatorSelect = screen.queryByRole('combobox', { name: /pick group combinator/i });
    expect(combinatorSelect).toBeInTheDocument();
  });

  it('should render all conditions in the group', () => {
    const props = getPropsFromEmptyRule(emptyRuleWithTwoConditions);
    renderWithProps(props);

    const conditions = screen.getAllByTestId('rcm-condition');
    expect(conditions.length).toEqual(2);
  });

  it('should call rule mutation `add` to add new condition', async () => {
    const user = userEvent.setup();
    const props = getPropsFromEmptyRule();
    renderWithProps(props);

    const newConditionButton = screen.getByRole('button', { name: /add another condition/i });
    await user.click(newConditionButton);
    expect(mockAddCondition).toBeCalledWith(
      {
        fact: '',
        operator: 'eq',
        value: '',
      },
      [0],
    );
  });
});
