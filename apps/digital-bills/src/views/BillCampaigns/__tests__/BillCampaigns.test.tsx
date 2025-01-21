import React from 'react';

import renderWithWrappers from '@apps/digital-bills/src/services/test/renderWithWrappers';
import { userEvent } from '@apps/digital-bills/src/services/test/test-utils';
import BillCampaigns from '@apps/digital-bills/src/views/BillCampaigns';

describe('BillCampaigns', () => {
  test('should render BillCampaigns component', async () => {
    const { getByText, getByRole } = renderWithWrappers(<BillCampaigns />);
    expect(getByText('Bill Campaigns')).toBeInTheDocument();

    const iframe = document.getElementById('billme-iframe');
    expect(iframe).toBeInTheDocument();
    let iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/bannerInBill');

    const campaignOptions = getByRole('combobox', { name: 'Select Listing' });
    await userEvent.click(campaignOptions);
    const adBelowBillOption = getByRole('option', { name: 'Ad Below Bill' });
    await userEvent.click(adBelowBillOption);
    iframeUrl = iframe?.getAttribute('src');
    expect(new URL(iframeUrl as string).pathname).toBe('/auto-engage/adBelowBill');
  });
});
