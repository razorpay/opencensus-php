import React from 'react';
import { act } from 'react-dom/test-utils';

import * as devices from 'merchant/components/Home/data';
import { sendPaymentLinkRequest } from 'merchant/views/Affordability/AssistedFinancing/queries';
import SendPaymentLinkModal from 'merchant/views/Affordability/AssistedFinancing/sendPaymentlinkModal';
import { render, screen, userEvent, waitFor } from 'test-utils';

import { createPaymentLinkResponse, paymentLinkFailureResponse } from './mocks/api';
import { emiPaymentLinkData } from './mocks/constants';

jest.mock('../queries', () => ({
  sendPaymentLinkRequest: jest.fn(),
}));

const renderApp = () => {
  const renderOutput = render(
    <SendPaymentLinkModal
      mobileNumber="9876598765"
      showPaymentLinkModal={true}
      setShowPaymentLinkModal={jest.fn()}
      paymentLinkData={emiPaymentLinkData}
      orderAmount="100"
    />,
  );
  return renderOutput;
};

describe('Affordability: SendPaymentLink', () => {
  test('should send payment link successfully', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => false);

    (sendPaymentLinkRequest as jest.Mock).mockResolvedValue(createPaymentLinkResponse);
    renderApp();
    expect(screen.getByText('Cancel')).toBeInTheDocument();

    const sendPaymentLinkButton = screen.getByTestId('send-payment-link-button');

    act(async () => {
      await userEvent.click(sendPaymentLinkButton);
    });

    await waitFor(() => {
      expect(screen.getByText('Successfully sent')).toBeInTheDocument();
    });

    const doneButton = screen.getByText('Done');

    act(async () => {
      await userEvent.click(doneButton);
    });
  });

  test('should show the error text if the payment link api fails', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => false);

    (sendPaymentLinkRequest as jest.Mock).mockResolvedValue(paymentLinkFailureResponse);
    renderApp();

    const sendPaymentLinkButton = screen.getByTestId('send-payment-link-button');

    act(async () => {
      await userEvent.click(sendPaymentLinkButton);
    });

    await waitFor(() => {
      expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });
  });

  test('should show the error text in mobile if the payment link  api fails', async () => {
    jest.spyOn(devices, 'isMobileDevice').mockImplementation(() => true);

    (sendPaymentLinkRequest as jest.Mock).mockResolvedValue(paymentLinkFailureResponse);
    renderApp();

    const sendPaymentLinkButton = screen.getByTestId('send-payment-link-button-mobile');

    act(async () => {
      await userEvent.click(sendPaymentLinkButton);
    });

    await waitFor(() => {
      expect(screen.getByText('Something went wrong')).toBeInTheDocument();
    });
  });
});
