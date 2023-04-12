import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { DashboardType } from 'merchant_common/views/Reports/types';

enum ReportsActionType {
  overview_tab_click = 'overview_tab_click',
  downloads_tab_click = 'downloads_tab_click',
  schedules_tab_click = 'schedules_tab_click',
  configs_fetch_failed = 'configs_fetch_failed',
}

enum OverviewActionType {
  load = 'load',
  overview_filter_interaction = 'overview_filter_interaction',
  cards_download_link_click = 'cards_download_link_click',
  download_report_button_click = 'download_report_button_click',
}

enum DownloadsActionType {
  download_filter_interaction = 'download_filter_interaction',
  download_report_button_click = 'download_report_button_click',
  download_file_click = 'download_file_click',
  download_file_success = 'download_file_success',
  download_file_failed = 'download_file_failed',
  expand_recipient_emails_click = 'expand_recipient_emails_click',
  pagination_click = 'pagination_click',
  downloads_page_load = 'downloads_page_load',
  downloads_logs_poll_start = 'downloads_logs_poll_start',
  downloads_logs_poll_stops = 'downloads_logs_poll_stops',
  downloads_logs_poll_failed = 'downloads_logs_poll_failed',
}

enum DownloadModalActionType {
  download_modal_open = 'download_modal_open',
  modal_section_click = 'modal_section_click',
  enable_emails_switch_toggle = 'enable_emails_switch_toggle',
  enable_custom_duration_switch_toggle = 'enable_custom_duration_switch_toggle',
  account_selection_field_interactions = 'account_selection_field_interactions',
  close_modal_click = 'close_modal_click',
  start_download_click = 'start_download_click',
  generate_report_req_failed = 'generate_report_req_failed',
  generate_report_req_success = 'generate_report_req_success',
  report_download_validation_error = 'report_download_validation_error',
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
    objectName: 'overview section',
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
    objectName: 'downloads section',
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
    objectName: 'Download Modal',
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
