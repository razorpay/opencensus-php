import React from 'react';
import { act } from 'react-dom/test-utils';
import { fireEvent, screen, userEvent, waitFor } from 'test-utils';

import { Condition } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/Condition';
import { emptyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/conditions';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { RuleCreatorEngine } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';
import { useRuleValidation } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import {
  byteLength,
  getUsagePercentage,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size';
import { lazyRenderACODComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers/acod';

const mockUpdateCondition = jest.fn();
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
      update: mockUpdateCondition,
    },
  })),
  useRuleValidation: jest.fn(),
}));

const getPropsFromEmptyRule = () => {
  const group = emptyRule.condition.conditions[0] as any;
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

const ConditionComponent = (...props: any) => {
  return (
    <RuleCreatorEngine
      defaultRule={emptyRule}
      sizeLimit="5kb"
      validator={() => ({ conditions: {}, actions: {} })}
      facts={Facts}
    >
      <Condition {...props[0]} />
    </RuleCreatorEngine>
  );
};
const renderWithProps = lazyRenderACODComponent(ConditionComponent);

describe('<Condition />', () => {
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

    const conditionSelect = screen.getByRole('combobox', { name: 'Select condition' });
    const operatorSelect = screen.getByRole('combobox', { name: 'Select operator' });
    const valueInput = screen.getByLabelText(/enter value/i);

    expect(screen.getByText(/op/i)).toBeInTheDocument();
    expect(conditionSelect).toBeInTheDocument();
    expect(operatorSelect).toBeInTheDocument();
    expect(valueInput).toBeInTheDocument();
  });

  it('should not render `remove` button when only condition in rule', () => {
    const group = emptyRule.condition.conditions[0] as any;
    const condition = group.conditions[0];
    const totalConditionGroups = 1;
    const indexPosition = 0;

    renderWithProps({ group, condition, totalConditionGroups, indexPosition });

    const button = screen.queryByRole('button', { name: /remove condition/i });
    expect(button).not.toBeInTheDocument();
  });

  it('should render `remove` button (on hover) when condition is removable', () => {
    const props = getPropsFromEmptyRule();
    props.totalConditionGroups = 2;

    renderWithProps(props);

    let button = screen.queryByRole('button', { name: /remove condition/i });
    expect(button).not.toBeInTheDocument();

    const conditionElement = screen.getByTestId('rcm-condition');
    fireEvent.mouseOver(conditionElement);
    button = screen.queryByRole('button', { name: /remove condition/i });
    expect(button).toBeInTheDocument();
  });

  it('should update condition', async () => {
    const user = userEvent.setup();
    const props = getPropsFromEmptyRule();

    const { getByRole } = renderWithProps(props);

    const conditionSelect = screen.getByRole('combobox', { name: /select condition/i });
    const valueInput = screen.getByLabelText(/enter value/i);

    await user.click(conditionSelect);
    await waitFor(() => expect(getByRole('listbox')).toBeVisible());

    act(() => {
      fireEvent.click(getByRole('option', { name: /quantity/i }));
    });
    await waitFor(() => {
      expect(mockUpdateCondition).toBeCalledTimes(3);
    });

    mockUpdateCondition.mockRestore();

    await user.type(valueInput, '10');
    await waitFor(() => {
      expect(mockUpdateCondition).toBeCalledWith('value', '10', [0, 0]);
    });
  });

  it('should render error if validation has one', async () => {
    (useRuleValidation as jest.Mock).mockReturnValueOnce({
      validationResult: {
        conditions: {
          group1: {
            condition1: {
              value: 'value error',
            },
          },
        },
      },
    });
    const props = getPropsFromEmptyRule();

    const { getByText } = renderWithProps(props);

    await waitFor(() => {
      expect(getByText(/value error/i)).toBeInTheDocument();
    });
  });

  it('should render error and clear it when value is changed', async () => {
    (useRuleValidation as jest.Mock).mockReturnValueOnce({
      validationResult: {
        conditions: {
          group1: {
            condition1: {
              value: 'value error',
            },
          },
        },
      },
    });
    const user = userEvent.setup();
    const props = getPropsFromEmptyRule();
    const { getByLabelText, getByText, queryByText } = renderWithProps(props);

    const valueInput = getByLabelText(/enter value/i);

    await waitFor(() => {
      expect(getByText(/value error/i)).toBeInTheDocument();
    });

    await user.type(valueInput, '10');
    await waitFor(() => {
      expect(queryByText(/value error/i)).not.toBeInTheDocument();
    });
  });
});
