import React from 'react';
import { render, screen, userEvent, waitFor } from 'test-utils';
import RecurringType from 'merchant/views/Transactions/SuccessRate/components/RecurringType';
import { METHOD_TYPES_MAP } from 'merchant/views/Transactions/SuccessRate/constants';
const initProps = {
  handleRecurringTypeChange: jest.fn(),
};

describe('<RecurringType/>', () => {
  test('should render recurring types of Cards recurring correctly on screen', () => {
    const currentMethodType = METHOD_TYPES_MAP.card_recurring;
    render(<RecurringType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('dropdown-types')).toBeVisible();
    expect(screen.getByText('Mandate type:')).toBeVisible();
  });
  test('should render recurring types of UPI AutoPay recurring correctly on screen', () => {
    const currentMethodType = METHOD_TYPES_MAP.upi_autopay;
    render(<RecurringType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('btn-types')).toBeVisible();
    expect(screen.getByText('Mandate Type:')).toBeVisible();
  });

  test('renders the correct dropdowns for Cards recurring', () => {
    const currentMethodType = METHOD_TYPES_MAP.card_recurring;
    render(<RecurringType {...initProps} currentMethodType={currentMethodType} />);

    expect(screen.getByTestId('auto-drop-down-item')).toBeInTheDocument();
    expect(screen.getByTestId('initial-drop-down-item')).toBeInTheDocument();
    expect(screen.getByTestId('auto,initial-drop-down-item')).toBeInTheDocument();
  });

  test('renders the correct number of buttons for UPI AutoPay', () => {
    const currentMethodType = METHOD_TYPES_MAP.upi_autopay;
    render(<RecurringType {...initProps} currentMethodType={currentMethodType} />);
    expect(screen.getByTestId('auto-btn-item')).toBeInTheDocument();
    expect(screen.getByTestId('initial-btn-item')).toBeInTheDocument();
  });

  test('should fire callback and sets active tab on selection for UPI AutoPay', async () => {
    const currentMethodType = METHOD_TYPES_MAP.upi_autopay;
    const selectedRecurringType = currentMethodType.defaultRecurringType;
    render(
      <RecurringType
        {...initProps}
        currentMethodType={currentMethodType}
        selectedRecurringType={selectedRecurringType}
      />,
    );
    const { handleRecurringTypeChange } = initProps;
    await userEvent.click(screen.getByTestId('initial-btn-item'));
    await waitFor(() => {
      expect(handleRecurringTypeChange).toHaveBeenCalledWith('initial');
    });

    await userEvent.click(screen.getByTestId('auto-btn-item'));
    await waitFor(() => {
      expect(handleRecurringTypeChange).toHaveBeenCalledWith('auto');
    });
  });
});
