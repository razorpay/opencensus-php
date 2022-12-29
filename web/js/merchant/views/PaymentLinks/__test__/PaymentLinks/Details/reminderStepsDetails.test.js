import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import ReminderStepsDetails from 'merchant/views/PaymentLinks/PaymentLinks/Details/ReminderStepsDetails';
import { render, screen, waitFor } from 'test-utils';

jest.mock('common/ui/PlaceholderLoader', () => () => <div>Placeholder loading</div>);

describe('Payment Link Details', () => {
  const renderApp = (props = {}) => {
    const nextReminders = [1619860800, 1620292800, 1620724800];
    return render(
      <ReminderStepsDetails {...props} isRemindersEnabled nextReminders={nextReminders} />,
    );
  };

  test('should render PaymentDetailsApp component without errors', () => {
    expect(renderApp).not.toThrowError();
  });

  test('should render the ReminderStepsDetails component with the correct data', () => {
    const isAutoRemindersUpdating = false;
    const isPaymentLinkClosed = true;

    const props = {
      isRemindersEnabled: true,
      isAutoRemindersUpdating,
      isPaymentLinkClosed,
    };

    renderApp(props);
    expect(screen.getByText(/01 May 2021/i)).toBeInTheDocument();
    expect(screen.getByText(/06 May 2021/i)).toBeInTheDocument();
    expect(screen.getByText(/11 May 2021/i)).toBeInTheDocument();
  });

  test('should render the PlaceholderLoader component in ReminderStepsDetails', () => {
    const nextReminders = [1619860800, 1620292800, 3620724800];
    const isAutoRemindersUpdating = true;
    const isPaymentLinkClosed = true;

    const props = {
      nextReminders,
      isRemindersEnabled: true,
      isAutoRemindersUpdating,
      isPaymentLinkClosed,
    };

    renderApp(props);
    waitFor(() => {
      expect(screen.getByText('Placeholder loading')).toBeInTheDocument();
    });
  });
});
