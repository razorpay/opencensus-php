import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import PaymentReceipt from 'merchant/views/Transactions/v1/Payments/components/PaymentReceipt';
import { createMemoryHistory } from 'history';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { Router } from 'react-router-dom';
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

export const AppWithRouter = ({ hash = '#paymentpages', ...rest }) => {
  const history = createMemoryHistory();
  history.push({ pathname: '/', hash });
  return (
    <Router history={history}>
      <PaymentReceipt {...defaultProps} {...rest} />
    </Router>
  );
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
