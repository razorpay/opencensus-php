import React from 'react';
import { Button, CloseIcon, Heading, Link, Text } from '@razorpay/blade/components';
import imageExportAll from 'assets/partner-dashboard/export-all-csv-icon.svg';

import { StyledConfirmGenerateReport } from './styles';

interface IConfirmGenerateReportProps {
  onDownload: () => void;
  closeModal: () => void;
}

const ConfirmGenerateReport = ({
  onDownload,
  closeModal,
}: IConfirmGenerateReportProps): JSX.Element => {
  const onGenerateClick = () => {
    onDownload();
    closeModal();
  };

  return (
    <StyledConfirmGenerateReport>
      <div className="modal-header">
        <div className="close" onClick={closeModal}>
          <CloseIcon size="medium" color="feedback.icon.neutral.intense" />
        </div>
      </div>
      <img src={imageExportAll} />
      <div className="export-all">
        <Heading size="medium">Export all (CSV)</Heading>
      </div>
      <div className="content">
        <Text size="medium">
          This report only contains data for affiliate accounts added during the <b>last month.</b>
        </Text>
      </div>
      <div className="action-buttons">
        <div className="secondary-btn">
          <Link variant="button" onClick={closeModal}>
            Cancel
          </Link>
        </div>
        <Button onClick={onGenerateClick}>Generate Report</Button>
      </div>
    </StyledConfirmGenerateReport>
  );
};
export default ConfirmGenerateReport;
