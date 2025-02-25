import { Box, Button, DownloadIcon, Heading } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';
import React from 'react';

import { fetchDownloadReportData } from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/queries';
import {
  getDownloadReportQueryParams,
  getExcelReportData,
} from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/utils';
import { TransactionsPagesMap } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { useStore } from '@federated/apps/shell/commonStore';
import { exportFileAsExcel } from '@libs/shared-utils';
import isEmpty from 'lodash/isEmpty';

interface IDisputeListHeader {
  isFetchingTableData: boolean;
  mid: string;
  showNotification: (args: { type: string; message: string }) => void;
}

const DisputeListHeader: React.FC<IDisputeListHeader> = ({ mid, isFetchingTableData }) => {
  const showNotification = useStore((state) => state.showNotification);

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
        isDisabled={isLoading || isFetchingTableData}
        accessibilityLabel="download-dispute-report-file"
        isLoading={isLoading}
        testID="download-testID"
      />
    </Box>
  );
};

export default DisputeListHeader;
