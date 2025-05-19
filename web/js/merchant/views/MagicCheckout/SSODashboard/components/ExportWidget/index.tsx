import React, { useState } from 'react';
import { showNotification } from 'merchant_common/reducers/notifications';

import { exportSSOMerchantData } from 'merchant/views/MagicCheckout/SSODashboard/api';
import { downloadFromUrl } from 'merchant/views/MagicCheckout/common/helpers';

import { Button, DownloadIcon } from '@razorpay/blade/components';

import type { TimeRange } from 'merchant/views/MagicCheckout/SSODashboard/types';

interface ExportWidgetProps {
  timeRange: TimeRange;
}

const ExportWidget = ({ timeRange }: ExportWidgetProps) => {
  const [isLoading, setIsLoading] = useState(false);
  const getDownloadLink = async () => {
    try {
      setIsLoading(true);
      const ssoMerchantData = await exportSSOMerchantData({
        from: timeRange.start.valueOf(),
        to: timeRange.end.valueOf(),
      });
      const fileLink = ssoMerchantData?.data?.file_url;
      if (fileLink) {
        downloadFromUrl(showNotification, fileLink);
      }
    } catch (err: any) {
      showNotification({
        type: 'error',
        message: err?.errors?.[0] || 'Something went wrong while downloading file',
      });
    } finally {
      setIsLoading(false);
    }
  };
  return (
    <Button
      variant="secondary"
      color="primary"
      size="medium"
      iconPosition="left"
      icon={DownloadIcon}
      onClick={getDownloadLink}
      isLoading={isLoading}
    >
      Export
    </Button>
  );
};

export default ExportWidget;
