import React from 'react';
import { connect } from 'react-redux';
import {
  OverViewBannerWrapper,
  BannerBGImage,
  OverViewContent,
  BannerAccentImage,
  ReportFeatureBtnWrapper,
} from './style';
import {
  Title,
  Text,
  Button,
  DownloadIcon,
  ReportModal,
  FileTextIcon,
  Box,
} from 'merchant_common/views/Reports/components';
import { useTheme } from 'merchant_common/views/Reports/hooks';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { openModal } from 'merchant_common/reducers/modals';

const mapStateToProps = ({ session }) => {
  const isSchedulesEnabled = Boolean(session.user.isRevampedReportsEnabled?.schedules);
  return { isSchedulesEnabled };
};

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

const OverviewBannerComponent = ({ loading, openModal, isSchedulesEnabled }): JSX.Element => {
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

  const handleScheduleClick = () => {
    trackOverviewSection({
      actionName: 'Schedule Report Button Click',
      dashboardType: dashboardType as DashboardType,
    });
    openModal({
      component: <ReportModal type="create_edit_schedule" dashboardType={dashboardType} />,
      size: 'custom',
    });
  };

  return (
    <OverViewBannerWrapper theme={theme}>
      <OverViewContent>
        <div>
          <Title contrast="low">
            {isSchedulesEnabled
              ? 'Generate & schedule reports for all your business transactions, settlements & subscription'
              : 'Generate reports for all your business transactions, settlements & subscriptions'}
          </Title>
          <Text variant="body" type="muted" contrast="low">
            All your products reports in one place, now with new and better interface.
          </Text>
        </div>
        <Box flexWrap="wrap" marginTop="spacing.11" display="flex">
          {isSchedulesEnabled && (
            <Box marginRight="spacing.4">
              <ReportFeatureBtnWrapper>
                <Button
                  onClick={handleScheduleClick}
                  isLoading={loading}
                  variant="primary"
                  icon={FileTextIcon}
                  iconPosition="left"
                  isFullWidth
                >
                  Schedule Report
                </Button>
              </ReportFeatureBtnWrapper>
            </Box>
          )}
          <ReportFeatureBtnWrapper>
            <Button
              onClick={handleDownloadClick}
              isLoading={loading}
              variant="secondary"
              icon={DownloadIcon}
              iconPosition="left"
              isFullWidth
            >
              Download Report
            </Button>
          </ReportFeatureBtnWrapper>
        </Box>
      </OverViewContent>
      <BannerBGImage theme={theme} />
      <BannerAccentImage theme={theme} />
    </OverViewBannerWrapper>
  );
};

export const OverviewBanner = connect(mapStateToProps, mapDispatchToProps)(OverviewBannerComponent);
