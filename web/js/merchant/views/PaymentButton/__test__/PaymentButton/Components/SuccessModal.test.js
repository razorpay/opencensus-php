import React from 'react';
import { render, screen } from 'test-utils';
import { App } from 'merchant/views/PaymentButton/__test__/mocks/fixtures/SuccessModal';

describe('Payment Button Component SuccessModal Component', () => {
  const renderApp = (props) => render(<App {...props} />);

  test('Payment Button SuccessModal App component should be defined', () => {
    expect(App).toBeDefined();
  });

  test('should render payment button success modal subheadings', () => {
    renderApp();
    expect(screen.getByText('Actions After a Successful Payment')).toBeInTheDocument();
    expect(screen.getByText('Show a custom message.')).toBeInTheDocument();
    expect(screen.getByText('Button updated successfully')).toBeInTheDocument();
  });

  test('should render payment button isEditExistingId heading', () => {
    const props = {
      isEditExistingId: false,
    };
    renderApp(props);
    expect(screen.getByText('Button created successfully')).toBeInTheDocument();
  });
});
