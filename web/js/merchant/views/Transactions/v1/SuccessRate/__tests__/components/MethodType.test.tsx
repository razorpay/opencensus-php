import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import MethodType from 'merchant/views/Transactions/v1/SuccessRate/components/MethodType';
import { METHOD_TYPES_MAP } from 'merchant/views/Transactions/v1/SuccessRate/constants';
const initProps = {
  handleMethodTypeChange: jest.fn(),
};

describe('<MethodType/>', () => {
  test('should render method types of Card correctly on screen', () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('method-types')).toBeVisible();
  });
  test('should render method types of Cards recurring correctly on screen', () => {
    const currentMethodType = METHOD_TYPES_MAP.card_recurring;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('method-types')).toBeVisible();
  });
  test('renders the correct method types for Card', () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('credit-btn-method-item')).toBeInTheDocument();
    expect(screen.getByTestId('debit-btn-method-item')).toBeInTheDocument();
    expect(screen.getByTestId('prepaid-btn-method-item')).toBeInTheDocument();
  });
  test('renders the correct method types for Cards recurring', () => {
    const currentMethodType = METHOD_TYPES_MAP.card_recurring;
    render(<MethodType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('credit-btn-method-item')).toBeInTheDocument();
    expect(screen.getByTestId('debit-btn-method-item')).toBeInTheDocument();
  });

  test('should fire callback and sets active tab on selection for Card', async () => {
    const currentMethodType = METHOD_TYPES_MAP.Card;
    const selectedMethodType = currentMethodType.defaultType;
    render(
      <MethodType
        {...initProps}
        currentMethodType={currentMethodType}
        selectedMethodType={selectedMethodType}
      />,
    );
    const { handleMethodTypeChange } = initProps;
    await userEvent.click(screen.getByTestId('credit-btn-method-item'));
    await waitFor(() => {
      expect(handleMethodTypeChange).toHaveBeenCalledWith('credit');
    });

    await userEvent.click(screen.getByTestId('debit-btn-method-item'));
    await waitFor(() => {
      expect(handleMethodTypeChange).toHaveBeenCalledWith('debit');
    });
    await userEvent.click(screen.getByTestId('prepaid-btn-method-item'));
    await waitFor(() => {
      expect(handleMethodTypeChange).toHaveBeenCalledWith('prepaid');
    });
  });
  test('should fire callback and sets active tab on selection for Cards recurring', async () => {
    const currentMethodType = METHOD_TYPES_MAP.card_recurring;
    const selectedMethodType = currentMethodType.defaultRecurringType;
    render(
      <MethodType
        {...initProps}
        currentMethodType={currentMethodType}
        selectedMethodType={selectedMethodType}
      />,
    );
    const { handleMethodTypeChange } = initProps;
    await userEvent.click(screen.getByTestId('credit-btn-method-item'));
    await waitFor(() => {
      expect(handleMethodTypeChange).toHaveBeenCalledWith('credit');
    });

    await userEvent.click(screen.getByTestId('debit-btn-method-item'));
    await waitFor(() => {
      expect(handleMethodTypeChange).toHaveBeenCalledWith('debit');
    });
  });
});
