import React from 'react';

import App from 'merchant/views/Subscriptions/RecurringPayments/List';
import { fetchRecurringPayments } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';
import { screen, within, server, render, waitForLoadingToFinish } from 'test-utils';
import 'jest-location-mock';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/views/Subscriptions/utils', () => ({
  ...jest.requireActual('merchant/views/Subscriptions/utils'),
  trackSearchEvent: jest.fn(),
}));

describe('Recurring Payments List', () => {
  beforeEach(async () => {
    server.use(fetchRecurringPayments());
    render(
      <App
        location={{
          search: '?someParam=someValue&',
          pathname: 'somePathname',
          href: 'example.com',
        }}
      />,
    );
    await waitForLoadingToFinish('table-spinner');
  });

  test('Should render Recurring Payments List Fields', () => {
    expect(
      screen.getByRole('link', {
        name: /documentation/i,
      }),
    ).toBeInTheDocument();

    [
      'payment id',
      'razorpay order id',
      'amount',
      'email',
      'contact',
      'created at',
      'status',
    ].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', {
          name: new RegExp(fieldLabel, 'i'),
        }),
      ).toBeInTheDocument();
    });
  });

  test('Should render Recurring Payments List Field values', () => {
    [
      'pay_l5g0sxs5jhbwnt',
      'order_l5fyvzvsogmg6w',
      'satanick.dutta@razorpay.com',
      '[+]91 8407 983457',
      '18 jan 2023, 02:40:53 pm',
      'captured',
    ].forEach((fieldLabel) => {
      expect(screen.getByRole('cell', { name: new RegExp(fieldLabel, 'i') })).toBeInTheDocument();
    });

    const cell = screen.getByRole('cell', {
      name: /amount-info ₹ - Indian Rupee \(inr\)/i,
    });
    within(cell).getByText(/^1$/i);
    expect(screen.getByText(/showing 1 - 1/i)).toBeInTheDocument();
  });
});
