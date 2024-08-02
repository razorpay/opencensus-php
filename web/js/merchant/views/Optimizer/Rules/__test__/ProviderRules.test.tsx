import React from 'react';
import { render } from 'test-utils';

import { ProviderRules } from '../ProviderRules';

describe('Optimizer Rule ProviderRules', () => {
  const MOCK_PROPS = {
    rules: {
      1: [
        {
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
      ],
    },
    readonly: false,
    parent: 'create-rule',
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
    update: jest.fn(),
    addNewRow: jest.fn(),
  };

  const renderApp = (props) => render(<ProviderRules {...props} />);

  it('should render without any errors', () => {
    expect(() => renderApp(MOCK_PROPS)).not.toThrowError();
  });

  it('should render with the elements', () => {
    const { getByText, getByRole, getByDisplayValue } = renderApp(MOCK_PROPS);

    expect(getByText('PRIORITY 1')).toBeInTheDocument();
    expect(getByText('Route')).toBeInTheDocument();
    expect(getByDisplayValue('100')).toBeInTheDocument();
    expect(getByRole('button', { name: 'Remove' })).toBeInTheDocument();
    expect(getByText('payment via')).toBeInTheDocument();
    expect(getByText('Add Another Provider')).toBeInTheDocument();
  });

  it('should render for readonly mode', () => {
    const props = {
      ...MOCK_PROPS,
      readonly: true,
      rules: {
        1: [
          {
            additional_attribute: [
              { name: 'provider_priority', value: '1' },
              { name: 'load', value: '100' },
            ],
            canary: {
              ramp_percent: 100,
              rule: null,
              use_canary: false,
            },
            created_at: '2024-07-01T09:48:42Z',
            created_by: '',
            default_expression: null,
            description: '',
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
                {
                  type: 'comparator',
                  value: '==',
                  operands: [
                    { type: 'variable', value: '$merchant.id', operands: null },
                    { type: 'string', value: 'ELi8nocD30pFkb', operands: null },
                  ],
                },
              ],
            },
            id: 'OTK9ffgl5OvMne',
            indexable: true,
            mode: null,
            name: '90738836769_test_rule_ELi8nocD30pFkb',
            score: 3,
            skip_on_failure: false,
            updated_at: '2024-07-22T10:13:55Z',
          },
        ],
      },
    };
    const { getByText } = renderApp(props);

    expect(getByText('PRIORITY 1')).toBeInTheDocument();
    expect(getByText(/Route/)).toBeInTheDocument();
    expect(getByText(/100%/)).toBeInTheDocument();
    expect(getByText(/Payments via/)).toBeInTheDocument();
    expect(getByText(/payu test edit 1/)).toBeInTheDocument();
  });
});
