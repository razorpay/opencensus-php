import React from 'react';

import copyToClipboard from 'common/utils/copyToClipboard';
import {
  renderWithQueryClient,
  server,
  queryClient,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/Details/__tests__/mocks/handlers';
import { exportLinkHandlers } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/Methods/IntlBankTransfer/ExportLinkBanner/__tests__/mocks/handlers';
import { screen, waitFor, userEvent } from 'test-utils';

import ExportLinkBanner from '../index';

jest.mock('common/utils/copyToClipboard', () => jest.fn());

describe('Test ExportLinkBanner', () => {
  beforeAll(() => server.listen());
  beforeEach(() => {
    server.use(exportLinkHandlers.success(), exportLinkHandlers.createExportLink());
  });
  afterEach(() => {
    server.resetHandlers();
    queryClient.clear();
  });
  afterAll(() => server.close());

  it('should fetch and render export link', async () => {
    renderWithQueryClient(<ExportLinkBanner />);

    await waitFor(() => expect(screen.getByText(/Here's the link/)).toBeInTheDocument());
    expect(screen.getByText('razorpay.com/export-link/@export_id')).toBeInTheDocument();
  });

  it('should copy the export link', async () => {
    renderWithQueryClient(<ExportLinkBanner />);

    await waitFor(() => expect(screen.getByText(/Here's the link/)).toBeInTheDocument());
    const copyLinkButton = screen.getByText('Copy link');
    await userEvent.click(copyLinkButton);

    expect(copyToClipboard).toHaveBeenCalledWith('https://razorpay.com/export-link/@export_id');
  });
});
