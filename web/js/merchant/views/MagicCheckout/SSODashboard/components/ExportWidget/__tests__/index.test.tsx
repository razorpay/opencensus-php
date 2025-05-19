import React from 'react';
import { render, screen, fireEvent, waitFor } from 'test-utils';
import moment from 'moment';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';
import ExportWidget from 'merchant/views/MagicCheckout/SSODashboard/components/ExportWidget';
import { exportSSOMerchantData } from 'merchant/views/MagicCheckout/SSODashboard/api';

// Mock the API and helper functions
jest.mock('merchant/views/MagicCheckout/SSODashboard/api', () => ({
  exportSSOMerchantData: jest.fn(),
}));

jest.mock('merchant/views/MagicCheckout/common/helpers', () => ({
  downloadFromUrl: jest.fn(),
}));

jest.mock('merchant_common/reducers/notifications', () => ({
  showNotification: jest.fn(),
}));

describe('ExportWidget', () => {
  const defaultProps = {
    timeRange: {
      start: moment().subtract(1, 'days'), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
      end: moment(), // nosemgrep: ssc-1e99e462-0fc5-4109-ad52-d2b5a7048232
    },
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('renders export button', () => {
    render(<ExportWidget {...defaultProps} />);
    expect(screen.getByText('Export')).toBeInTheDocument();
  });

  it('handles successful export', async () => {
    const mockFileUrl = 'https://example.com/file.csv';
    (exportSSOMerchantData as jest.Mock).mockResolvedValue({ data: { file_url: mockFileUrl } });

    render(<ExportWidget {...defaultProps} />);

    fireEvent.click(screen.getByText('Export'));

    await waitFor(() => {
      expect(exportSSOMerchantData).toHaveBeenCalledWith({
        from: defaultProps.timeRange.start.valueOf(),
        to: defaultProps.timeRange.end.valueOf(),
      });
      expect(downloadFromUrl).toHaveBeenCalledWith(expect.any(Function), mockFileUrl);
    });
  });

  it('handles export error', async () => {
    const errorMessage = 'Export failed';
    (exportSSOMerchantData as jest.Mock).mockRejectedValue({ errors: [errorMessage] });

    render(<ExportWidget {...defaultProps} />);

    fireEvent.click(screen.getByText('Export'));

    await waitFor(() => {
      expect(exportSSOMerchantData).toHaveBeenCalled();
      expect(downloadFromUrl).not.toHaveBeenCalled();
    });
  });
});
