import React from 'react';
import { screen, render, server, waitFor } from 'test-utils';
import { successRateHandler } from 'merchant/views/EcosystemDowntimes/__tests__/mocks/handlers';
import SuccessRateSummary from 'merchant/views/EcosystemDowntimes/components/SuccessRateSummary';

const initProps = {
  isMobile: false,
  srKey: 'card.issuer.SBIN',
  instrument: 'SBIN',
  method: 'card',
};

describe('<SuccessRateSummary/>', () => {
  test('Should render tiles with necessary sr info', async () => {
    server.use(successRateHandler({ isSuccess: true }));
    render(<SuccessRateSummary {...initProps} />);

    expect(screen.getByLabelText('sr-summary-loader')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByLabelText('sr-summary-container')).toHaveTextContent(
        'Successful Payments',
      );
    });

    expect(screen.getByLabelText('sr-summary-container')).toHaveTextContent(
      'Unsuccessful Payments',
    );
    expect(screen.getByLabelText('sr-summary-container')).toHaveTextContent('33,003');
  });

  test('Should render alert box if no payments made with correct content', async () => {
    server.use(successRateHandler({ isSuccess: true, isNoPayments: true }));
    render(<SuccessRateSummary {...initProps} />);

    expect(screen.getByLabelText('sr-summary-loader')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByLabelText('no-payments-sr')).toHaveTextContent(
        'No payments were attempted via State Bank Of India (Cards) in the past one week',
      );
    });
  });

  test('Should render error box if endpoint fails', async () => {
    server.use(successRateHandler({ isSuccess: false }));
    render(<SuccessRateSummary {...initProps} />);

    expect(screen.getByLabelText('sr-summary-loader')).toBeInTheDocument();

    await waitFor(() => {
      expect(screen.getByTestId('Notification--error')).toHaveTextContent(
        'Something went wrong while fetching success rate',
      );
    });

    expect(screen.getByLabelText('sr-summary-error')).toHaveTextContent(
      'Something went wrong while fetching successful payments',
    );
  });
});
