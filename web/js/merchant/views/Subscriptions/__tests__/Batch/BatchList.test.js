import React from 'react';
import { screen, server, render, waitForLoadingToFinish } from 'test-utils';
import App from 'merchant/views/Subscriptions/Batch/List';
import { fetchBatches } from 'merchant/views/Subscriptions/__tests__/mocks/fixtures/fixtures';

jest.mock('common/ui/HeaderAction', () => ({ children }) => <div>{children}</div>);

describe('Render Batch List', () => {
  beforeEach(async () => {
    window.rzpQ = {
      onbr: function onbr() {
        return {
          success: jest.fn(),
        };
      },
    };
    server.use(fetchBatches());
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

  test('Should render Batch List Fields', () => {
    const docElem = screen.getAllByRole('link', { name: /documentation/i })[0];
    const docUrl =
      'https://razorpay.com/docs/recurring-payments/dashboard-operations/batch-operations/';

    expect(docElem).toHaveAttribute('href', docUrl);
    expect(screen.getAllByText(/upload new batch/i)[0]).toBeInTheDocument();

    ['batch id', 'batch name', 'count', 'type', 'status', 'actions'].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', { name: new RegExp(fieldLabel, 'i') }),
      ).toBeInTheDocument();
    });
  });

  test('Should render Batch List Field values', () => {
    [
      'batch_hqrmnxlddbfkzv',
      'sample_recurring_payments - sample_recurring_payme ...',
      '^1$',
      'processed',
      'showing 1 - 1',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(screen.getAllByText(/^recurring charge$/i)[1]).toBeInTheDocument();
    expect(screen.getByRole('button', { name: /download/i })).toBeInTheDocument();
  });
});

describe('Render batch if user has isRegistrationLinkSupervisorRole', () => {
  beforeEach(async () => {
    window.rzpQ = {
      onbr: function onbr() {
        return {
          success: jest.fn(),
        };
      },
    };
    server.use(fetchBatches(true));
    render(
      <App
        location={{
          search: '?someParam=someValue&',
          pathname: 'somePathname',
          href: 'example.com',
        }}
      />,
      {
        initialState: {
          session: {
            user: {
              isRegistrationLinkSupervisorRole: true,
              isOrgAllowedFunctionality: () => true,
              findTag: () => false,
            },
          },
        },
      },
    );
    await waitForLoadingToFinish();
  });
  test('Should render Batch List Fields if user has isRegistrationLinkSupervisorRole', () => {
    ['batch id', 'batch name', 'count', 'type', 'status', 'actions'].forEach((fieldLabel) => {
      expect(
        screen.getByRole('columnheader', { name: new RegExp(fieldLabel, 'i') }),
      ).toBeInTheDocument();
    });
  });

  test('Should render Batch List Field values', () => {
    [
      'batch_hqrmnxlddbfkzv',
      'sample_recurring_payments - sample_recurring_payme ...',
      '^1$',
      '^Registration Link$',
      'processed',
      'showing 1 - 1',
    ].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: /download/i })).toBeInTheDocument();
  });
});
