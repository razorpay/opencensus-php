import React from 'react';
import {
  screen,
  within,
  server,
  render,
  waitForLoadingToFinish,
  userEvent,
  waitFor,
} from 'test-utils';
import App from 'merchant/views/Subscriptions/RecurringPayments/List';
import { fetchRecurringPayments } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';
import { trackSearchEvent } from 'merchant/views/Subscriptions/utils';
import 'jest-location-mock';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);
jest.mock('merchant/views/Transactions/v1/Payments/components/PaymentsListFilter', () => ({
  ...jest.requireActual('merchant/views/Transactions/v1/Payments/components/PaymentsListFilter'),
  __esModule: true,
  default: ({ onSubmit, onClearAnalytics }) => (
    <div>
      <button onClick={onSubmit.bind({}, 'submit')}>Search</button>
      <button onClick={onClearAnalytics}>Clear</button>
    </div>
  ),
}));
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
    await waitForLoadingToFinish();
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
      'showing 1 - 1',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });

    const cell = screen.getByRole('cell', {
      name: /amount-info ₹ - ₹ \(inr\)/i,
    });
    within(cell).getByText(/^1$/i);
  });

  test('Should render Recurring Payments List Search Form', async () => {
    const searchBtn = screen.getByRole('button', { name: /search/i });
    const clearBtn = screen.getByRole('button', { name: /clear/i });

    await userEvent.click(clearBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'clear',
        expect.objectContaining({
          eventStartLabel: 'payment.search',
        }),
      );
    });
    await userEvent.click(searchBtn);
    await waitFor(() => {
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'initiate',
        expect.objectContaining({
          eventStartLabel: 'payment.search',
        }),
      );
      expect(trackSearchEvent).toHaveBeenCalledWith(
        'submit',
        expect.objectContaining({
          eventStartLabel: 'payment.search',
        }),
      );
    });
  });
});
