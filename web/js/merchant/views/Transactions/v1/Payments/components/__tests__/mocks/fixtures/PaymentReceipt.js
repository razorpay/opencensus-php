import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentReceipt from 'merchant/views/Transactions/v1/Payments/components/PaymentReceipt';
import * as NotificationsActions from 'merchant_common/reducers/notifications';

// Mock `window.location` with Jest spies and extend expect
import 'jest-location-mock';

export const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');
export const defaultProps = {
  payment: {
    id: '456',
  },
  onUpdateReferenceId: jest.fn(),
  tracking: {
    trackEvent: jest.fn(),
  },
};

export const App = (props) => {
  return <PaymentReceipt {...defaultProps} {...props} />;
};

beforeAll(() => {
  window.rzpQ = {
    component: jest.fn(),
    paymentPages: () => ({
      success: jest.fn(),
    }),
  };
  jest.useFakeTimers();
  jest.spyOn(window, 'setTimeout');
});

beforeEach(() => {
  showNotificationSpy.mockClear();
});
