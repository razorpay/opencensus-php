import React from 'react';
import { connect } from 'react-redux';
import {
  BannerBGImage,
  OverViewContent,
  BannerAccentImage,
  ReportFeatureBtnWrapper,
} from './style';
import {
  Heading,
  Text,
  Button,
  DownloadIcon,
  ReportModal,
  FileTextIcon,
  Box,
} from 'merchant_common/views/Reports/components';
import { useReportsSplitzExperiments, useTheme } from 'merchant_common/views/Reports/hooks';
import { useDashboardType } from 'merchant_common/views/Reports/contexts/ReportsContext';
import { trackOverviewSection } from 'merchant_common/views/Reports/configs/analytics.config';
import { DashboardType } from 'merchant_common/views/Reports/types';
import { openModal } from 'merchant_common/reducers/modals';

const mapDispatchToProps = (dispatch) => ({
  openModal: (modal) => dispatch(openModal(modal)),
});

const mapStateToProps = (state) => ({
  user: state.session.user,
  org: state.session.org,
});

const OverviewBannerComponent = ({ loading, openModal, user, org }): JSX.Element => {
  const { theme } = useTheme();
  const dashboardType = useDashboardType();
  const { isSchedulesEnabled } = useReportsSplitzExperiments(org, user);

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
    <Box
      borderBottomLeftRadius="large"
      borderBottomRightRadius="large"
      position="relative"
      overflow="hidden"
      marginBottom="spacing.5"
      minHeight="254px"
      backgroundColor="surface.background.gray.intense"
    >
      <OverViewContent>
        <Box>
          <Heading size="large" color="surface.text.gray.normal">
            {isSchedulesEnabled
              ? 'Generate & schedule reports for all your business transactions, settlements & subscription'
              : 'Generate reports for all your business transactions, settlements & subscriptions'}
          </Heading>
          <Text variant="body" color="surface.text.gray.muted">
            All your products reports in one place, now with new and better interface.
          </Text>
        </Box>
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
    </Box>
  );
};

export const OverviewBanner = connect(mapStateToProps, mapDispatchToProps)(OverviewBannerComponent);
