import React from 'react';
import { userEvent } from '@testing-library/user-event';
import { render } from 'test-utils';

import Select from '../Select';

describe('Optimizer Rules Select', () => {
  const mockProps = {
    selected: [],
    class: '',
    placeholder: 'Select Provider',
    options: [
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
    multiple: undefined,
    select: jest.fn(),
    searchable: true,
    selectedOperator: undefined,
  };

  const renderApp = (props) => render(<Select {...props} />);

  it('should render Select component without any errors', () => {
    expect(() => renderApp(mockProps)).not.toThrowError();
  });

  it('should render Select component with placeholder', () => {
    const { getByPlaceholderText, queryByDisplayValue } = renderApp(mockProps);
    expect(getByPlaceholderText('Select Provider')).toBeInTheDocument();
    expect(queryByDisplayValue('payu test edit 1')).toBeNull();
  });

  it('should render Select component with options', async () => {
    const { getByPlaceholderText, getByText } = renderApp(mockProps);
    const input = getByPlaceholderText('Select Provider');
    await userEvent.click(input);
    expect(getByText('payu test edit 1')).toBeInTheDocument();
    expect(getByText('PaytmNew123')).toBeInTheDocument();
    expect(getByText('razorpay')).toBeInTheDocument();
    expect(getByText('Smart router')).toBeInTheDocument();
    expect(getByText('RECOMMENDED')).toBeInTheDocument();
    expect(getByText('Recommended for better success rate')).toBeInTheDocument();
  });

  it('should render Select component with selected options', () => {
    const props = {
      ...mockProps,
      selected: [
        {
          id: 'paytm_GV75eSy3la4HOB',
          name: 'PaytmNew123',
          value: 'paytm_GV75eSy3la4HOB',
        },
      ],
    };
    const { getByDisplayValue } = renderApp(props);
    expect(getByDisplayValue('PaytmNew123')).toBeInTheDocument();
  });

  it('should render disabled options with message', async () => {
    const props = {
      ...mockProps,
      options: [
        ...mockProps.options,
        {
          id: 'paytm_OWSv2Z6LyRfC94',
          name: 'paytm integration audit',
          value: 'paytm_OWSv2Z6LyRfC94',
          disabled: true,
          disabled_message: 'Selected method is not supported on this provider.',
        },
      ],
    };
    const { getByText, getByPlaceholderText } = renderApp(props);
    const input = getByPlaceholderText('Select Provider');
    await userEvent.click(input);
    expect(getByText('paytm integration audit')).toBeInTheDocument();
    expect(getByText('Selected method is not supported on this provider.')).toBeInTheDocument();
    const disabledList = getByText('paytm integration audit').closest('li');
    expect(disabledList).toHaveClass('disabled');
  });
});
