import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { DashboardType } from 'merchant_common/views/Reports/types';

enum ReportsActionType {
  'Overview Tab Click' = 'Overview Tab Click',
  'Downloads Tab Click' = 'Downloads Tab Click',
  'Schedules Tab Click' = 'Schedules Tab Click',
  'Configs Fetch Failed' = 'Configs Fetch Failed',
}

enum OverviewActionType {
  'Loaded' = 'Loaded',
  'Overview Filter Interaction' = 'Overview Filter Interaction',
  'Cards Download Link Click' = 'Cards Download Link Click',
  'Download Report Button Click' = 'Download Report Button Click',
}

enum DownloadsActionType {
  'Download Filter Interaction' = 'Download Filter Interaction',
  'Download Report Button Click' = 'Download Report Button Click',
  'Download File Click' = 'Download File Click',
  'Report File Download Success' = 'Report File Download Success',
  'Report File Download Failed' = 'Report File Download Failed',
  'Expand Recipient Emails Click' = 'Expand Recipient Emails Click',
  'Pagination Click' = 'Pagination Click',
  'Downloads Section Loaded' = 'Downloads Section Loaded',
  'Downloads Logs Poll Start' = 'Downloads Logs Poll Start',
  'Downloads Logs Poll Stop' = 'Downloads Logs Poll Stop',
  'Downloads Logs Poll Failed' = 'Downloads Logs Poll Failed',
}

enum DownloadModalActionType {
  'Download Report Modal Opened' = 'Download Report Modal Opened',
  'Modal Section Clicked' = 'Modal Section Clicked',
  'Enable Emails Switch Toggled' = 'Enable Emails Switch Toggled',
  'Enable Custom Duration Switch Toggled' = 'Enable Custom Duration Switch Toggled',
  'Account Selection Field Interactions' = 'Account Selection Field Interactions',
  'Close Report Modal Clicked' = 'Close Report Modal Clicked',
  'Start Report Download Btn Clicked' = 'Start Report Download Btn Clicked',
  'Generate Report Req Failed' = 'Generate Report Req Failed',
  'Generate Report Req Success' = 'Generate Report Req Success',
  'Report Download Validation Error' = 'Report Download Validation Error',
}

const track = ({ properties, dashboardType, ...args }) => {
  analyticsTrack({
    ...args,
    properties: {
      ...getCommonAnalyticsProperties(window.rzp_user),
      ...properties,
      dashboard_type: dashboardType,
    },
  });
};

export const trackOverviewSection = ({
  actionName,
  properties,
  dashboardType,
}: {
  actionName: keyof typeof OverviewActionType;
  properties?: Record<string, unknown>;
  dashboardType: DashboardType;
}) => {
  track({
    objectName: 'Overview Section',
    actionName,
    screen: 'reports/overview',
    properties: {
      location: 'Overview',
      ...properties,
    },
    dashboardType,
  });
};

export const trackDownloadsSection = ({
  actionName,
  properties,
  dashboardType,
}: {
  actionName: keyof typeof DownloadsActionType;
  properties?: Record<string, unknown>;
  dashboardType: DashboardType;
}) => {
  track({
    objectName: 'Downloads Section',
    actionName,
    screen: 'reports/downloads',
    properties: {
      location: 'Downloads',
      ...properties,
    },
    dashboardType,
  });
};

export const trackDownloadModal = ({
  actionName,
  properties,
  dashboardType,
}: {
  actionName: keyof typeof DownloadModalActionType;
  properties?: Record<string, unknown>;
  dashboardType: DashboardType;
}) => {
  track({
    objectName: 'Download Report Modal',
    actionName,
    screen: 'reports/downloads/download modal',
    properties: {
      location: 'Downloads',
      ...properties,
    },
    dashboardType,
  });
};

export const trackReportsSection = ({
  actionName,
  properties,
  dashboardType,
}: {
  actionName: keyof typeof ReportsActionType;
  properties?: Record<string, unknown>;
  dashboardType: DashboardType;
}) => {
  track({
    objectName: 'Reports Generic',
    actionName,
    screen: 'reports',
    properties: {
      location: 'Reports',
      ...properties,
    },
    dashboardType,
  });
};
