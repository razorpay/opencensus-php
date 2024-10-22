import React from 'react';
import { Box, Button, DownloadIcon, Heading } from '@razorpay/blade/components';
import { useMutation } from '@tanstack/react-query';

import { fetchDownloadReportData } from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/queries';
import { getExcelReportData } from 'apps/self-serve/src/App/Transactions/v2/Disputes/components/DisputeListHeader/utils';
import { TransactionsPagesMap } from 'apps/self-serve/src/App/Transactions/v2/common/constants';
import { track } from 'apps/self-serve/src/App/Transactions/v2/common/tracking';
import { useStore } from 'shell/commonStore';
import { exportFileAsExcel } from '@dashboard/shared-utils/rzp-utils';

interface IDisputeListHeader {
  pathname: string;
  mid: string;
  showNotification: (args: { type: string; message: string }) => void;
}

const DisputeListHeader: React.FC<IDisputeListHeader> = ({ pathname, mid }) => {
  const showNotification = useStore((state) => state.showNotification);

  const { mutate: getDownloadReports, isLoading } = useMutation({
    mutationFn: fetchDownloadReportData,
    onSuccess: (reportData) => {
      exportFileAsExcel(getExcelReportData({ mid, downloadData: reportData }));
    },
    onError: () => {
      showNotification({ type: 'error', message: 'Failed to fetch download report' });
    },
  });

  const handleDownloadReportClick = () => {
    track({
      objectName: 'Download Disputes report',
      properties: { section: TransactionsPagesMap[pathname] },
    });
    getDownloadReports();
  };

  return (
    <Box display="flex" justifyContent="space-between" alignItems="center" marginBottom="spacing.6">
      <Heading>Disputes</Heading>
      <Button
        variant="tertiary"
        onClick={handleDownloadReportClick}
        icon={DownloadIcon}
        isDisabled={isLoading}
        accessibilityLabel="download-dispute-report-file"
        isLoading={isLoading}
        testID="download-testID"
      />
    </Box>
  );
};

export default DisputeListHeader;
