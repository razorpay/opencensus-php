import React from 'react';
import { render } from 'test-utils';

import { ProviderRow } from '../ProviderRow';

describe('Optimizer Rule ProviderRow', () => {
  const mockProps = {
    rule: {
      name: 'xx_xx_xx',
      skip_on_failure: false,
      additional_attribute: [
        { name: 'provider_priority', value: '1' },
        { name: 'load', value: '100' },
      ],
      expression: {
        type: 'logical',
        value: '&&',
        operands: [
          {
            type: 'comparator',
            value: '==',
            operands: [
              { type: 'variable', value: '$provider.id', operands: null },
              { value: '', type: 'string', operands: null },
            ],
          },
          {
            type: 'comparator',
            value: 'in',
            operands: [
              { type: 'string', value: 'live', operands: null },
              { type: 'variable', value: '$payment.rule_mode', operands: null },
            ],
          },
        ],
      },
    },
    update: jest.fn(),
    providers: [
      {
        id: 'paytm_GV75eSy3la4HOB',
        name: 'PaytmNew123',
        value: 'paytm_GV75eSy3la4HOB',
      },
      {
        id: 'payu_HdvEjdKKJMBX89',
        name: 'payu test edit 1',
        value: 'payu_HdvEjdKKJMBX89',
      },
      {
        id: 'razorpay',
        name: 'razorpay',
        value: 'razorpay',
      },
      {
        id: 'smart_router',
        name: 'Smart router',
        value: 'smart_router',
      },
    ],
    readonly: false,
    onClose: jest.fn(),
  };

  const renderApp = (props) => render(<ProviderRow {...props} />);

  it('should render ProviderRow without any errors', () => {
    expect(() => renderApp(mockProps)).not.toThrowError();
  });

  it('should render ProviderRow with the elements', () => {
    const { getByText } = renderApp(mockProps);
    expect(getByText('Route')).toBeInTheDocument();
    expect(getByText('payment via')).toBeInTheDocument();
  });

  it('should render ProviderRow for readOnly true', () => {
    const props = {
      ...mockProps,
      readonly: true,
      rule: {
        ...mockProps.rule,
        expression: {
          type: 'logical',
          value: '&&',
          operands: [
            {
              type: 'comparator',
              value: '==',
              operands: [
                { type: 'variable', value: '$provider.id', operands: null },
                { value: 'payu_HdvEjdKKJMBX89', type: 'string', operands: null },
              ],
            },
            {
              type: 'comparator',
              value: 'in',
              operands: [
                { type: 'string', value: 'live', operands: null },
                { type: 'variable', value: '$payment.rule_mode', operands: null },
              ],
            },
          ],
        },
      },
    };
    const { getByText } = renderApp(props);
    expect(getByText(/Route/)).toBeInTheDocument();
    expect(getByText(/100%/)).toBeInTheDocument();
    expect(getByText(/Payments via/)).toBeInTheDocument();
    expect(getByText(/payu test edit 1/)).toBeInTheDocument();
  });
});
