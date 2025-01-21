import React from 'react';

import CommunicationCampaigns from '@apps/digital-bills/src/views/CommunicationCampaigns';
import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';

describe('CommunicationCampaigns', () => {
  test('should render CommunicationCampaigns component', async () => {
    const { getByText, getByRole } = renderWithWrappers(<CommunicationCampaigns />);
    expect(getByText('Communication Campaigns')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    let iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/sms');

    const campaignOptions = getByRole('combobox', { name: 'Select Listing' });
    await userEvent.click(campaignOptions);
    const emailOption = getByRole('option', { name: 'E-Mail' });
    await userEvent.click(emailOption);
    iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/email');
  });
});
