import React from 'react';
import {
  OverViewBannerWrapper,
  BannerBGImage,
  DownloadReportButton,
  OverViewContent,
  BannerAccentImage,
} from './style';
import { Title, Text, Button, DownloadIcon } from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { DashboardType } from 'merchant_common/views/Reports/types';

export const OverviewBanner = ({ loading, history }): JSX.Element => {
  const { theme } = useTheme();
  const dashboardType = useDashboardType();

  const handleDownloadClick = () => {
    trackOverviewSection({
      actionName: 'download_report_button_click',
      dashboardType: dashboardType as DashboardType,
    });
    history.push(
      `${dashboardType === 'partner' ? 'partner' : ''}/reports/downloads?modal=download_report`,
    );
  };

  return (
    <OverViewBannerWrapper theme={theme}>
      <OverViewContent>
        <div>
          <Title contrast="low">
            Generate reports for all your business transactions, settlements & subscriptions
          </Title>
          <Text variant="body" type="muted" contrast="low">
            All your products reports in one place, now with new and better interface.
          </Text>
        </div>

        <DownloadReportButton>
          <Button
            onClick={handleDownloadClick}
            isLoading={loading}
            variant="secondary"
            icon={DownloadIcon}
            iconPosition="left"
          >
            Download Report
          </Button>
        </DownloadReportButton>
      </OverViewContent>
      <BannerBGImage theme={theme} />
      <BannerAccentImage theme={theme} />
    </OverViewBannerWrapper>
  );
};
