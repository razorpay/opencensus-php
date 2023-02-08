import React from 'react';
import { render, userEvent, screen } from 'test-utils';
import {
  App,
  defaultProps,
} from 'merchant/views/PartnerDashboard/SubMerchant/components/__test__/mocks/fixtures/ConfirmGenerateReport';

describe('ConfirmGenerateReport', () => {
  test('clicking Generate Report should call onDownload', async () => {
    render(<App />, {});
    const generateReportBtn = screen.getByText('Generate Report');
    await userEvent.click(generateReportBtn);
    expect(defaultProps.onDownload).toHaveBeenCalled();
  });

  test('clicking Cancel should call closeModal', async () => {
    render(<App />, {});
    const cancelBtn = screen.getByText('Cancel');
    await userEvent.click(cancelBtn);
    expect(defaultProps.closeModal).toHaveBeenCalled();
  });
});
