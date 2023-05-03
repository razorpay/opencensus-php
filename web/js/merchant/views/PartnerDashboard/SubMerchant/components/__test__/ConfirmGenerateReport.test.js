import React from 'react';
import { render, userEvent, screen } from 'test-utils';
import ConfirmGenerateReport from 'merchant/views/PartnerDashboard/SubMerchant/components/ConfirmGenerateReport';

const defaultProps = {
  onDownload: jest.fn(),
  closeModal: jest.fn(),
};

describe('ConfirmGenerateReport', () => {
  test('clicking Generate Report should call onDownload', async () => {
    render(<ConfirmGenerateReport {...defaultProps} />, {});
    const generateReportBtn = screen.getByText('Generate Report');
    await userEvent.click(generateReportBtn);
    expect(defaultProps.onDownload).toHaveBeenCalled();
  });

  test('clicking Cancel should call closeModal', async () => {
    render(<ConfirmGenerateReport {...defaultProps} />, {});
    const cancelBtn = screen.getByText('Cancel');
    await userEvent.click(cancelBtn);
    expect(defaultProps.closeModal).toHaveBeenCalled();
  });
});
