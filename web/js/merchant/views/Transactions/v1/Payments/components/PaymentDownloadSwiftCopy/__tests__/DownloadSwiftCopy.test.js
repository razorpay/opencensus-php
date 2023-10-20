import React from 'react';

import * as ajax from 'merchant/utils/ajax';
import PaymentDownloadSwiftCopy from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/DownloadSwiftCopy';
import {
  trackDownloadButtonClicked,
  trackDownloadSuccess,
  trackDownloadFailure,
} from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/analytics';
import * as NotificationsActions from 'merchant_common/reducers/notifications';
import { render, userEvent, waitFor, screen } from 'test-utils';

jest.mock(
  'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy/analytics',
  () => ({
    trackDownloadButtonClicked: jest.fn(),
    trackDownloadSuccess: jest.fn(),
    trackDownloadFailure: jest.fn(),
  }),
);

const merchantFetchSpy = jest.spyOn(ajax, 'merchantFetch');
const showNotificationSpy = jest.spyOn(NotificationsActions, 'showNotification');

const renderComponent = (props) => {
  return render(<PaymentDownloadSwiftCopy paymentId="pay_123" {...props} />);
};

describe('Test PaymentDownloadSwiftCopy', () => {
  test('should call handleDownload and open window when button is clicked and data is returned', async () => {
    merchantFetchSpy.mockResolvedValueOnce({
      data: {
        items: [
          {
            signed_url: 'https://example.com',
          },
        ],
      },
    });

    global.window.open = jest.fn();

    renderComponent();

    await userEvent.click(screen.getByRole('button'));

    // check that merchantFetch was called
    expect(merchantFetchSpy).toHaveBeenCalledWith({
      url: 'merchant/pxb/documents?payment_ids=pay_123',
    });

    // check new window is opened for download
    await waitFor(() => {
      expect(trackDownloadButtonClicked).toHaveBeenCalled();
      expect(global.window.open).toHaveBeenCalledWith('https://example.com', '_blank');
      expect(trackDownloadSuccess).toHaveBeenCalled();
    });

    expect(showNotificationSpy).not.toHaveBeenCalled();
  });

  test('should call showNotification when there is an error during download', async () => {
    merchantFetchSpy.mockResolvedValueOnce({
      success: false,
    });

    global.window.open = jest.fn();

    renderComponent();

    await userEvent.click(screen.getByRole('button'));

    // check that merchantFetch was called
    expect(merchantFetchSpy).toHaveBeenCalledWith({
      url: 'merchant/pxb/documents?payment_ids=pay_123',
    });
    expect(trackDownloadButtonClicked).toHaveBeenCalled();

    await waitFor(() => {
      expect(trackDownloadFailure).toHaveBeenCalled();
    });

    expect(global.window.open).not.toHaveBeenCalled();
  });

  test('should render button without text when asIcon is true', () => {
    renderComponent({ asIcon: true });

    expect(screen.getByRole('button')).toBeInTheDocument();
    expect(screen.queryByRole('button', { name: 'Download SWIFT Copy' })).not.toBeInTheDocument();
  });
});
