import React from 'react';
import { Box, Button, DownloadIcon, Heading } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import isEmpty from 'lodash/isEmpty';
import { connect } from 'react-redux';

import { exportFileAsExcel } from 'common/utils/rzp-utils';
import { fetchDownloadReportData } from 'merchant/views/Transactions/v2/Disputes/components/DisputeListHeader/queries';
import {
  getDownloadReportQueryParams,
  getExcelReportData,
} from 'merchant/views/Transactions/v2/Disputes/components/DisputeListHeader/utils';
import { TransactionsPagesMap } from 'merchant/views/Transactions/v2/common/constants';
import { track } from 'merchant/views/Transactions/v2/common/tracking';
import { showNotification } from 'merchant_common/reducers/notifications';

interface IDisputeListHeader {
  mid: string;
  isFetchingTableData: boolean;
  isDownloadDisabled: boolean;
  showNotification: (args: { type: string; message: string }) => void;
}

const DisputeListHeader: React.FC<IDisputeListHeader> = ({
  mid,
  isFetchingTableData,
  isDownloadDisabled,
  showNotification,
}) => {
  const { mutate: getDownloadReports, isLoading } = useMutation({
    mutationFn: (params: string) => fetchDownloadReportData(params),
    onSuccess: (reportData) => {
      if (isEmpty(reportData)) {
        showNotification({ type: 'error', message: 'No records available to download' });
      } else {
        exportFileAsExcel(getExcelReportData({ mid, downloadData: reportData }));
      }
    },
    onError: () => {
      showNotification({ type: 'error', message: 'Failed to fetch download report' });
    },
  });

  const handleDownloadReportClick = () => {
    if (!isFetchingTableData && !isLoading) {
      getDownloadReports(getDownloadReportQueryParams());
      track({
        objectName: 'Download Disputes report',
        properties: { section: TransactionsPagesMap[window.location.pathname] },
      });
    }
  };

  return (
    <Box display="flex" justifyContent="space-between" alignItems="center" marginBottom="spacing.6">
      <Heading>Disputes</Heading>
      <Button
        variant="tertiary"
        onClick={handleDownloadReportClick}
        icon={DownloadIcon}
        isDisabled={isLoading || isFetchingTableData || isDownloadDisabled}
        accessibilityLabel="download-dispute-report-file"
        isLoading={isLoading}
        testID="download-testID"
      />
    </Box>
  );
};

export default connect(null, {
  showNotification,
})(DisputeListHeader);
