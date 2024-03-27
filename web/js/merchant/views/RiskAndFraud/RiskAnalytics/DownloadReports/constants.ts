import { AnalyticsEntity, DownloadReport, DownloadReports, Preset } from './types';

const FRAUD_DOWNLOAD_REPORTS: DownloadReport = {
  heading: 'Download list of fraudulent transactions',
  description:
    'Need more data around the highest frauds on other parameters such as IP address, email and phone number? Just download the entire list of transactions with frauds to help you do analysis.',
  note: 'In case selected duration exceeds 90 days, a list for the latest 90 days in that is considered for generating the list.',
};

const DISPUTES_DOWNLOAD_REPORTS: DownloadReport = {
  heading: 'Download list of disputes',
  description:
    'Need more data around the highest disputes on other parameters such as IP address, email and phone number? Just download the entire list of transactions with disputes to analyse.',
  note: 'In case selected duration exceeds 90 days, a list for the latest 90 days in that is considered for generating the list.',
};
const RISK_DECLINED_DOWNLOAD_REPORTS: DownloadReport = {
  heading: 'Download list of transactions declined due to risk',
  description:
    'Need more data around the risk declines on parameters such as IP address, email and phone number? Just download the entire list of transactions with disputes to help you do the analysis yourself.',
};

export const DOWNLOAD_REPORTS: DownloadReports = {
  [AnalyticsEntity.FRAUD]: FRAUD_DOWNLOAD_REPORTS,
  [AnalyticsEntity.DISPUTES]: DISPUTES_DOWNLOAD_REPORTS,
  [AnalyticsEntity.RISK_DECLINED]: RISK_DECLINED_DOWNLOAD_REPORTS,
};

const FRAUD_MODAL_CONTENT = {
  title: 'Download fraudulent transaction list',
  filename: 'fraudulent-transaction-list',
};

const DISPUTES_MODAL_CONTENT = {
  title: 'Download disputes transaction list',
  filename: 'disputes-transaction-list',
};

const RISK_DECLINED_MODAL_CONTENT = {
  title: 'Download risk declined list',
  filename: 'risk-declined-list',
};

export const REPORT_MODAL_CONTENT = {
  [AnalyticsEntity.FRAUD]: FRAUD_MODAL_CONTENT,
  [AnalyticsEntity.DISPUTES]: DISPUTES_MODAL_CONTENT,
  [AnalyticsEntity.RISK_DECLINED]: RISK_DECLINED_MODAL_CONTENT,
};

export const DEFAULT_REPORTS_PRESET = {
  label: 'Last 6 months',
  value: '6m',
  duration: 6,
  unit: 'months',
};

export const REPORTS_PRESETS: Preset[] = [
  { label: 'Last 2 weeks', value: '14d', duration: 14, unit: 'days' },
  { label: 'Last 1 month', value: '30d', duration: 30, unit: 'days' },
  { label: 'Last 2 months', value: '60d', duration: 60, unit: 'days' },
  { label: 'Last 3 months', value: '90d', duration: 90, unit: 'days' },
  { label: 'Custom', value: 'custom', duration: 0, unit: 'custom' },
];

export const CONFIG_IDS = {
  [AnalyticsEntity.FRAUD]: 'config_NaHH9xCxCvNZu0',
  [AnalyticsEntity.DISPUTES]: 'config_NaHBHYCGlrH2bd',
  [AnalyticsEntity.RISK_DECLINED]: 'config_NaH2pA6cArDYmi',
};

export const REPORT_POST_SUCCESS = 'Report request submitted successfully.';

export const REPORT_POST_INVALID_RES = 'Unable to process the report. Please try again later.';
