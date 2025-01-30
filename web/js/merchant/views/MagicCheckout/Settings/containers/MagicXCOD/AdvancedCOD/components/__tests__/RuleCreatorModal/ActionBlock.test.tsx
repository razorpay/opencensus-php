import React from 'react';
import { screen, userEvent } from 'test-utils';

import { ActionBlock } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/RuleCreatorModal/ActionBlock';
import { Facts } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/constants';
import { RuleCreatorEngine } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator';
import {
  useRuleValidation,
  useRule,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks';
import {
  byteLength,
  getUsagePercentage,
} from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size';
import { lazyRenderACODComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers/acod';

import { emptyRule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/conditions';
import { shippingProfiles } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/__tests__/mocks/shipping-profiles';

import type { RuleType as ACODRuleType } from 'merchant/reducers/magicCheckout/magicxACODRules/types';
import type { Rule } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/types';

jest.mock('merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/size', () => ({
  byteLength: jest.fn(),
  getUsagePercentage: jest.fn(),
}));
jest.mock('merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks', () => ({
  ...(jest.requireActual(
    'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/RuleCreator/hooks',
  ) as object),
  useRule: jest.fn(),
  useRuleMutations: jest.fn(() => ({})),
  useRuleValidation: jest.fn(),
}));

const getProps = (type: ACODRuleType = 'shipping', rule: Rule = emptyRule) => {
  return {
    type,
    rule,
    shippingProfiles,
  };
};

const ConditionBlockComponent = (props: any) => {
  const { rule, ...restProps } = props;

  return (
    <RuleCreatorEngine
      defaultRule={rule}
      sizeLimit="5kb"
      validator={() => ({ conditions: {}, actions: {} })}
      facts={Facts}
    >
      <ActionBlock {...restProps} />
    </RuleCreatorEngine>
  );
};
const renderWithProps = lazyRenderACODComponent(ConditionBlockComponent);

describe('<ActionBlock />', () => {
  beforeEach(() => {
    (byteLength as jest.Mock).mockReturnValue(10);
    (getUsagePercentage as jest.Mock).mockReturnValue(0);
    (useRule as jest.Mock).mockReturnValue({
      rule: emptyRule,
    });
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
    const props = getProps('shipping');
    renderWithProps(props);

    const actionBlock = screen.getByTestId('rcm-actionblock');

    expect(actionBlock).toBeInTheDocument();
  });

  it('should render action selector', () => {
    const props = getProps('shipping');
    (useRule as jest.Mock).mockReturnValueOnce({
      rule: props.rule,
    });
    renderWithProps(props);

    const actionSelect = screen.queryByRole('combobox', { name: /select action/i });
    expect(actionSelect).toBeInTheDocument();
  });

  it('should render shipping action types when rule type is shipping', async () => {
    const user = userEvent.setup();
    const props = getProps('shipping');
    (useRule as jest.Mock).mockReturnValueOnce({
      rule: props.rule,
    });
    const { getAllByText } = renderWithProps(props);
    const actionSelect = screen.getByRole('combobox', { name: /select action/i });
    await user.click(actionSelect);
    expect(getAllByText(/shipping methods/i).length).toEqual(4);
  });

  it('should render payment action types when rule type is payment', async () => {
    const user = userEvent.setup();
    const props = getProps('payment');
    (useRule as jest.Mock).mockReturnValueOnce({
      rule: props.rule,
    });
    const { getAllByText } = renderWithProps(props);
    const actionSelect = screen.getByRole('combobox', { name: /select action/i });
    await user.click(actionSelect);
    expect(getAllByText(/(payment|prepaid|cod) methods/i).length).toEqual(4);
  });

  it('[shipping] should render combobox when action type is show/hide specific', () => {
    const props = getProps('shipping');
    const rule = JSON.parse(JSON.stringify(props.rule));
    props.rule = rule;
    rule.actions[0].type = 'SHOW_SPECIFIC_SHIPPING';
    (useRule as jest.Mock).mockReturnValue({
      rule,
    });

    renderWithProps(props);
    const paramsInput = screen.getByRole('combobox', { name: /select shipping methods/i });
    expect(paramsInput).toBeInTheDocument();
  });

  it('[payment] should render text input when action type is show/hide specific', () => {
    const props = getProps('payment');
    const rule = JSON.parse(JSON.stringify(props.rule));
    props.rule = rule;
    rule.actions[0].type = 'SHOW_SPECIFIC_PAYMENT';
    (useRule as jest.Mock).mockReturnValue({
      rule,
    });

    const { getByLabelText } = renderWithProps(props);
    const paramsInput = getByLabelText(/enter comma separated values/i);
    expect(paramsInput).toBeInTheDocument();
  });
});
