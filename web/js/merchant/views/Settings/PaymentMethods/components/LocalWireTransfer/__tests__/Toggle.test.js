import { screen, render, userEvent, waitFor } from 'test-utils';

import Toggle from 'merchant/views/Settings/PaymentMethods/components/LocalWireTransfer/Toggle';

const renderComponent = (props = {}) => {
  return render(<Toggle {...props} />);
};

describe('Tests for toggle', () => {
  test('Component should render without breaking', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('Hide details text should be visible when isOpen is same as currency', () => {
    const props = {
      isOpen: 'usd',
      currency: 'usd',
    };

    renderComponent(props);

    expect(screen.getByText('Hide details')).toBeInTheDocument();
  });

  test('Account details text should be visible when isOpen is different from currency', () => {
    const props = {
      isOpen: 'swift',
      currency: 'usd',
    };

    renderComponent(props);

    expect(screen.getByText('Account details')).toBeInTheDocument();
  });

  test('Passed function should be called when passed through props', async () => {
    const onToggleClick = jest.fn();

    const props = {
      isOpen: 'swift',
      currency: 'usd',
      onToggleClick,
    };

    renderComponent(props);

    const toggle = screen.getByText('Account details');
    await userEvent.click(toggle);

    await waitFor(() => expect(onToggleClick).toHaveBeenCalled());
    expect(onToggleClick).toHaveBeenCalledTimes(1);
  });
});
