import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { render, screen, waitFor } from '@testing-library/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import { Reports } from 'merchant/views/Wallet/Reports';
import * as modals from 'merchant_common/reducers/modals';
import { userEvent } from 'test-utils';

const storeState = {
  session: {
    user: {
      user: {
        contact_mobile: '9999999999',
      },
      merchant: {
        country_code: 'IN',
      },
    },
  },
};

const ReportsTab = () => {
  return (
    <QueryClientProvider
      client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}
    >
      <Provider store={storeWithInitialState(storeState)}>
        <BladeProvider themeTokens={bladeTheme}>
          <Reports openModal={jest.fn()} />
        </BladeProvider>
      </Provider>
    </QueryClientProvider>
  );
};

const modalsSpy = jest.spyOn(modals, 'openModal');

// @WARNING: Invalid UT Written, Needs to fixed by the POC
describe.skip('Wallet > Reports > Download', () => {
  test('Should render download report button', async () => {
    render(<ReportsTab />);

    const downloadButton = screen.getByText('Download Report');
    await waitFor(() => {
      expect(downloadButton).toBeInTheDocument();
    });
  });

  test('Should open download modal when button is clicked', async () => {
    render(<ReportsTab />);
    await waitFor(async () => {
      const downloadButton = screen.getByText('Download Report');
      expect(downloadButton).toBeInTheDocument();
      await userEvent.click(downloadButton);
      expect(modalsSpy).toHaveBeenCalled();
    });
  });
});

// @WARNING: Invalid UT Written, Needs to fixed by the POC
describe.skip('Wallet > Reports > Table', () => {
  test('Should display table with expected columns', async () => {
    render(<ReportsTab />);

    await waitFor(() => {
      expect(screen.getByText('Duration Covered')).toBeInTheDocument();
      expect(screen.getByText('Name')).toBeInTheDocument();
      expect(screen.getByText('Format')).toBeInTheDocument();
      expect(screen.getByText('Email')).toBeInTheDocument();
      expect(screen.getByText('Status')).toBeInTheDocument();
      expect(screen.getByText('Download')).toBeInTheDocument();
    });
  });

  test('Should display table with expected rows', async () => {
    render(<ReportsTab />);

    await waitFor(() => {
      expect(screen.getAllByRole('rowgroup').length).toBe(2);
      expect(screen.getByText('Payment Links')).toBeInTheDocument();
      expect(screen.getByText('csv')).toBeInTheDocument();
      expect(screen.getByText('Success')).toBeInTheDocument();
    });
  });
});
