import React from 'react';
import { connect } from 'react-redux';
import {
  OverViewBannerWrapper,
  BannerBGImage,
  DownloadReportButton,
  OverViewContent,
  BannerAccentImage,
} from './style';
import {
  Title,
  Text,
  Button,
  DownloadIcon,
  ReportModal,
} from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { openModal } from 'merchant_common/reducers/modals';

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

const OverviewBannerComponent = ({ loading, openModal }): JSX.Element => {
  const { theme } = useTheme();
  const dashboardType = useDashboardType();

  const handleDownloadClick = () => {
    trackOverviewSection({
      actionName: 'Download Report Button Click',
      dashboardType: dashboardType as DashboardType,
    });
    openModal({
      component: <ReportModal type="download_report" dashboardType={dashboardType} />,
      size: 'custom',
    });
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

export const OverviewBanner = connect(null, mapDispatchToProps)(OverviewBannerComponent);
