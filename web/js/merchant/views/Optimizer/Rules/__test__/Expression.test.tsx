import React from 'react';
import { render } from '@testing-library/react';
import { userEvent } from '@testing-library/user-event';

import { PARAMETERS } from 'merchant/views/Optimizer/utils';

import { Expression } from '../Expression';

describe('Optimizer Rules Expression', () => {
  const mockProps = {
    expression: {},
    update: jest.fn(),
    parameters: PARAMETERS,
    readonly: false,
    onClose: jest.fn(),
  };
  const renderApp = (props) => render(<Expression {...props} />);

  it('should render Expression component without any errors', () => {
    expect(() => renderApp(mockProps)).not.toThrowError();
  });

  it('should render Exression component for input when readOnly is false', async () => {
    const { getByText, getByPlaceholderText } = renderApp(mockProps);
    expect(getByText('When')).toBeInTheDocument();
    const parameterElem = getByPlaceholderText('Select Parameter');
    expect(parameterElem).toBeInTheDocument();
    expect(getByText('is')).toBeInTheDocument();
    const connectionElem = getByPlaceholderText('Select Connection');
    expect(connectionElem).toBeInTheDocument();
    expect(getByPlaceholderText('Select Comparing Value')).toBeInTheDocument();
    await userEvent.click(parameterElem);
    expect(getByText('Channels')).toBeInTheDocument();
    expect(getByText('Website, Android, iOS')).toBeInTheDocument();
    expect(getByText('Payment Method')).toBeInTheDocument();
    expect(getByText('Card, Netbanking, UPI Intent, UPI Collect')).toBeInTheDocument();
    await userEvent.click(connectionElem);
    expect(getByText('One Of')).toBeInTheDocument();
    expect(getByText('You can select multiple comparing value')).toBeInTheDocument();
  });

  it('should render Exression component for input when readOnly is false for methods', async () => {
    const props = {
      ...mockProps,
      expression: {
        type: 'comparator',
        value: 'in',
        operands: [
          {
            type: 'variable',
            value: '$payment.navigator_method',
            operands: null,
          },
          {
            type: null,
            value: '',
            operands: null,
          },
        ],
      },
    };
    const { getByText, getByDisplayValue, getByPlaceholderText } = renderApp(props);
    expect(getByText('When')).toBeInTheDocument();
    expect(getByDisplayValue('Payment Method')).toBeInTheDocument();
    expect(getByText('is')).toBeInTheDocument();
    expect(getByDisplayValue('One Of')).toBeInTheDocument();
    const valueElem = getByPlaceholderText('Select Comparing Value');
    expect(valueElem).toBeInTheDocument();
    await userEvent.click(valueElem);
    expect(getByPlaceholderText('Search')).toBeInTheDocument();
    expect(getByText('Card')).toBeInTheDocument();
    expect(getByText('Netbanking')).toBeInTheDocument();
  });

  it('should render Exression component for input when readOnly is true', () => {
    const props = {
      ...mockProps,
      expression: {
        type: 'comparator',
        value: 'in',
        operands: [
          {
            type: 'variable',
            value: '$payment.navigator_method',
            operands: null,
          },
          {
            type: 'array',
            value: 'card,netbanking',
            operands: null,
          },
        ],
      },
      readOnly: true,
    };
    const { getByText, getByDisplayValue } = renderApp(props);
    expect(getByText('When')).toBeInTheDocument();
    expect(getByDisplayValue('Payment Method')).toBeInTheDocument();
    expect(getByDisplayValue('One Of')).toBeInTheDocument();
    expect(getByDisplayValue('Card,Netbanking')).toBeInTheDocument();
  });
});
