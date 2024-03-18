import { OpenModalPayload, ShowNotificationType } from 'common/typings';

export enum AnalyticsEntity {
  FRAUD = 'fraud',
  DISPUTES = 'disputes',
  RISK_DECLINED = 'risk_declined',
}

export type ReportPresetUnit = 'days' | 'months' | 'custom';
export interface Preset {
  label: string;
  value: string;
  duration: number;
  unit: ReportPresetUnit;
}

export type ReportInitialState = {
  startDate: number | null;
  endDate: number | null;
  preset: Preset;
};

export interface DownloadReport {
  heading: string;
  description: string;
  note?: string;
}

export type DownloadReports = {
  [key in AnalyticsEntity]: DownloadReport; // Mapped object type
};

export interface DownloadReportsProps {
  entity: AnalyticsEntity;
  availableEmails: string[];
  generatedBy: string;
  openModal: (m: OpenModalPayload) => void;
  closeModal: () => void;
  showNotification: ShowNotificationType;
}

export interface ReportModalProps {
  entity: AnalyticsEntity;
  availableEmails: string[];
  generatedBy: string;
  onCloseCallback: () => void;
  showNotification: ShowNotificationType;
}

export interface SelectedRangeType {
  startDate: moment.Moment;
  endDate: moment.Moment;
}
