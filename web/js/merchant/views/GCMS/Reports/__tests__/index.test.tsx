import React from 'react';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { render, screen, waitFor } from '@testing-library/react';
import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import Reports from 'merchant/views/GCMS/Reports';
import * as modals from 'merchant_common/reducers/modals';
import { userEvent } from 'test-utils';

import { GCMSReportLogResponse } from './mocks/fixtures';

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
const variantOn = { razorpay_gcms: { variables: { result: 'on' } } };

const defaultAbExperiments = variantOn;
const mockAbExperiments = defaultAbExperiments;

jest.mock('common/splitz', () => ({
  useSplitzService: () => ({ abExperiments: mockAbExperiments }),
}));
const ReportsView = () => {
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
describe.skip('GCMS > Reports > Download', () => {
  test('Should render download report button', () => {
    render(<ReportsView />);

    const downloadButton = screen.getByText('Download Report');
    expect(downloadButton).toBeInTheDocument();
  });

  test('Should open download modal when button is clicked', async () => {
    render(<ReportsView />);
    const downloadButton = screen.getByText('Download Report');
    await userEvent.click(downloadButton);
    waitFor(() => {
      expect(modalsSpy).toBeCalled();
    });
  });
});

// @WARNING: Invalid UT Written, Needs to fixed by the POC
describe.skip('GCMS > Reports > Table', () => {
  test('Should display table with expected columns', async () => {
    render(<ReportsView />);
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
    render(<ReportsView />);
    await waitFor(() => {
      expect(screen.getAllByTestId('file-type').length).toBe(
        GCMSReportLogResponse.data.items.length,
      );
      expect(screen.getByText('Payment Links')).toBeInTheDocument();
      expect(screen.getByText('csv')).toBeInTheDocument();
      expect(screen.getByText('Success')).toBeInTheDocument();
    });
  });
});
