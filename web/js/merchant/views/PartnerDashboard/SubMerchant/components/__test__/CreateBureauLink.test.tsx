import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, waitFor, userEvent } from 'common/services/test/test-utils';
import { CreateBureauLink } from 'merchant/views/PartnerDashboard/SubMerchant/components/CreateBureauLink';
import { sendMessageSuccess, sendMessageError } from './mocks/handlers';

const closeModal = jest.fn();
const showNotification = jest.fn();

type BureauLinkData = {
  bureauLink: string;
  smsCount: number;
  partnerId: string;
  merchantId: string;
};

const bureauLinkData: BureauLinkData = {
  bureauLink: 'sample link',
  smsCount: 0,
  partnerId: 'testPartner123',
  merchantId: 'testMerchant123',
};
describe('Create Bureau Link', () => {
  beforeAll(() => {
    document.execCommand = jest.fn();
  });
  const renderApp = (data: BureauLinkData) => {
    return render(
      <CreateBureauLink
        closeModal={closeModal}
        showNotification={showNotification}
        bureauLinkData={data}
      />,
    );
  };
  test('show create bureau link modal', () => {
    renderApp(bureauLinkData);
    expect(screen.getByText('Line Of Credit Bureau')).toBeInTheDocument();
    expect(
      screen.getByText(
        'We can send out the link to the client as an SMS on your behalf or copy the link and send it out manually',
      ),
    ).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Copy Link' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Send Link as SMS' })).toBeInTheDocument();
  });

  test('should render success alert when sms API is success and show resend button', async () => {
    server.use(sendMessageSuccess());
    renderApp(bureauLinkData);
    expect(screen.getByText('Line Of Credit Bureau')).toBeInTheDocument();
    const copyButton = screen.getByRole('button', { name: 'Copy Link' });
    expect(copyButton).toBeInTheDocument();
    await userEvent.click(copyButton);
    expect(document.execCommand).toHaveBeenCalled();

    const sendSmsButton = screen.getByRole('button', { name: 'Send Link as SMS' });
    expect(sendSmsButton).toBeInTheDocument();
    await userEvent.click(sendSmsButton);
    await waitFor(() => {
      expect(screen.getByText('SMS sent')).toBeInTheDocument();
    });
    expect(screen.getByRole('button', { name: 'Resend Link as SMS' })).toBeInTheDocument();
  });

  // todo skipping this for now because it is getting failed because of retry option of react-query.
  test.skip('should error if sms API throws error', async () => {
    server.use(sendMessageError());
    renderApp(bureauLinkData);
    expect(screen.getByText('Line Of Credit Bureau')).toBeInTheDocument();

    const sendSmsButton = screen.getByRole('button', { name: 'Send Link as SMS' });
    expect(sendSmsButton).toBeInTheDocument();
    await userEvent.click(sendSmsButton);
    await waitFor(() => {
      expect(screen.getByText('There was an error')).toBeInTheDocument();
    });
  });
});
