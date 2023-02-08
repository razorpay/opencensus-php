import React from 'react';
import ConfirmGenerateReport from 'merchant/views/PartnerDashboard/SubMerchant/components/ConfirmGenerateReport';

export const defaultProps = {
  onDownload: jest.fn(),
  closeModal: jest.fn(),
};

export const App = (props) => {
  return <ConfirmGenerateReport {...defaultProps} {...props} />;
};
