import { render, screen } from 'test-utils';

import ErrorContainer from 'merchant/views/Settings/PaymentMethods/components/InstrumentContainer/ErrorContainer';

const renderComponent = (props = {}) => {
  return render(<ErrorContainer {...props} />);
};

describe('tests for Error container', () => {
  test('Component should render without breaking', () => {
    expect(renderComponent()).toBeDefined();
  });

  test('Component should display message if passed through props', () => {
    const message = 'Dummy test message';
    renderComponent({ message });
    expect(screen.getByText(message)).toBeInTheDocument();
  });

  test('Component should display action if passed through props', () => {
    const action = <button>Click me!</button>;
    renderComponent({ action });
    expect(screen.getByText('Click me!')).toBeInTheDocument();
  });

  test('Info icon should be visible', () => {
    renderComponent();
    expect(screen.getByTestId('info-icon')).toBeInTheDocument();
  });
});
