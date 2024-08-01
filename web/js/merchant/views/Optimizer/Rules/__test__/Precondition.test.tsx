import React from 'react';
import { render } from 'test-utils';

import { PARAMETERS } from 'merchant/views/Optimizer/utils';

import { Precondition } from '../Precondition';

describe('Optimizer Rules Precondition', () => {
  const mockProps = {
    precondition: {
      type: null,
      value: '',
      operands: [
        { operands: null, type: null, value: '' },
        { operands: null, type: null, value: '' },
      ],
    },
    update: jest.fn(),
    parameters: PARAMETERS,
    readonly: false,
    parent: '',
  };

  const renderApp = (props) => render(<Precondition {...props} />);

  it('should render Precondition component without any errors', () => {
    expect(() => renderApp(mockProps)).not.toThrowError();
  });

  it('should render Precondition component with elements', () => {
    const { getByText } = renderApp(mockProps);
    expect(getByText('Add Another Condition')).toBeInTheDocument();
  });

  it('should render Precondition component with selected details', () => {
    const props = {
      ...mockProps,
      precondition: {
        type: 'comparator',
        value: '==',
        operands: [
          { operands: null, type: 'variable', value: '$payment.navigator_method' },
          { operands: null, type: 'string', value: 'card' },
        ],
      },
    };
    const { getByDisplayValue } = renderApp(props);
    expect(getByDisplayValue('Payment Method')).toBeInTheDocument();
    expect(getByDisplayValue('Equal to')).toBeInTheDocument();
    expect(getByDisplayValue('card')).toBeInTheDocument();
  });
});
