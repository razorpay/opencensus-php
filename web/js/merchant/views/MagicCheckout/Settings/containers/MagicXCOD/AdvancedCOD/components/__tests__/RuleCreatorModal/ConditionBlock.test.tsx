import React from 'react';
import { screen, userEvent } from 'test-utils';

import { ConditionBlock } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ConditionBlock';
import {
  emptyRule,
  emptyRuleWithTwoConditionGroups,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/conditions';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { RuleCreatorEngine } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';
import { useRuleValidation } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import {
  byteLength,
  getUsagePercentage,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size';
import { createDefaultConditionGroup } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/state/defaults';
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
  return {
    rule,
  };
};

const ConditionBlockComponent = (...props: any) => {
  return (
    <RuleCreatorEngine
      defaultRule={props[0].rule}
      sizeLimit="5kb"
      validator={() => ({ conditions: {}, actions: {} })}
      facts={Facts}
    >
      <ConditionBlock />
    </RuleCreatorEngine>
  );
};
const renderWithProps = lazyRenderACODComponent(ConditionBlockComponent);

describe('<ConditionBlock />', () => {
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

    const conditionBlock = screen.getByTestId('rcm-conditionblock');
    const conditionGroup = screen.getByTestId('rcm-conditiongroup');

    expect(conditionBlock).toBeInTheDocument();
    expect(conditionGroup).toBeInTheDocument();
  });

  it('should not render combinator selector if only single condition group in block', () => {
    const props = getPropsFromEmptyRule();
    renderWithProps(props);

    const combinatorSelect = screen.queryByRole('combobox', { name: /pick block combinator/i });
    expect(combinatorSelect).not.toBeInTheDocument();
  });

  it('should render combinator selector if >1 condition groups in block', () => {
    const props = getPropsFromEmptyRule(emptyRuleWithTwoConditionGroups);
    renderWithProps(props);

    const combinatorSelect = screen.queryByRole('combobox', { name: /pick block combinator/i });
    expect(combinatorSelect).toBeInTheDocument();
  });

  it('should render all condition groups in the block', () => {
    const props = getPropsFromEmptyRule(emptyRuleWithTwoConditionGroups);
    renderWithProps(props);

    const conditionGroups = screen.getAllByTestId('rcm-conditiongroup');
    expect(conditionGroups.length).toEqual(2);
  });

  it('should call rule mutation `add` to add new condition group', async () => {
    const user = userEvent.setup();
    const props = getPropsFromEmptyRule();
    renderWithProps(props);

    const newConditionButton = screen.getByRole('button', { name: /add another block/i });
    await user.click(newConditionButton);
    expect(mockAddCondition).toBeCalledWith(createDefaultConditionGroup([1]), []);
  });
});
