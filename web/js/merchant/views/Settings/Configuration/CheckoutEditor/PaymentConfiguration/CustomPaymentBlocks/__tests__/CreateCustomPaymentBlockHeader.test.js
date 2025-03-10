import React from 'react';
import { render, screen, fireEvent } from 'test-utils';
import '@testing-library/jest-dom/extend-expect';
import CreateCustomPaymentBlockHeader from 'merchant/views/Settings/Configuration/CheckoutEditor/PaymentConfiguration/CustomPaymentBlocks/CreateCustomPaymentBlockHeader';

describe('CreateCustomPaymentBlockHeader', () => {
  const handleCreateNewCustomBlock = jest.fn();

  const renderComponent = (props = {}) => {
    return render(
      <CreateCustomPaymentBlockHeader
        handleCreateNewCustomBlock={handleCreateNewCustomBlock}
        hasMultipleCustomBlocks={false}
        {...props}
      />,
    );
  };

  test('should render title and description correctly when hasMultipleCustomBlocks is false', () => {
    renderComponent();
    expect(screen.getByText('Create a custom payment block')).toBeInTheDocument();
    expect(screen.getByText('Group your preferred payment methods together')).toBeInTheDocument();
  });

  test('should render title and description correctly when hasMultipleCustomBlocks is true', () => {
    renderComponent({ hasMultipleCustomBlocks: true });
    expect(screen.getByText('Your saved payment blocks')).toBeInTheDocument();
    expect(screen.getByText('Group your preferred payment methods together')).toBeInTheDocument();
  });

  test('should render Button when hasMultipleCustomBlocks is false', () => {
    renderComponent();
    expect(screen.getByRole('button', { name: /Create new custom block/ })).toBeInTheDocument();
  });

  test('should render Link when hasMultipleCustomBlocks is true', () => {
    renderComponent({ hasMultipleCustomBlocks: true });
    expect(screen.getByRole('button', { name: /Create/ })).toBeInTheDocument();
  });

  test('should call handleCreateNewCustomBlock when Button is clicked', () => {
    renderComponent();
    fireEvent.click(screen.getByRole('button', { name: /Create new custom block/ }));
    expect(handleCreateNewCustomBlock).toHaveBeenCalledTimes(1);
  });

  test('should call handleCreateNewCustomBlock when Link is clicked', () => {
    renderComponent({ hasMultipleCustomBlocks: true });
    fireEvent.click(screen.getByRole('button', { name: /Create/ }));
    expect(handleCreateNewCustomBlock).toHaveBeenCalledTimes(1);
  });
});
