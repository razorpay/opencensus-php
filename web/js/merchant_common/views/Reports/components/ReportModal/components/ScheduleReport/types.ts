import { AccountStateType } from 'merchant_common/views/Reports/types/account';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { CustomConfigType, DashboardType } from 'merchant_common/views/Reports/types';
import { ScheduleServerPayload, ScheduleType } from 'merchant_common/views/Reports/types/schedule';
import { ReportsFetchHeaders } from 'merchant_common/views/Reports/api/types';

export interface RepetitionType {
  label: string;
  value: string;
  weekIndex?: number;
  dateIndex?: number;
}

export interface DataDurationType {
  label: string;
  value: string;
}

export interface ScheduleModalParams {
  /**
   * If passed, modal will be of edit type.
   */
  scheduleData?: ScheduleType;
  selectedConfig?: string;
}

export interface ScheduleModalPropsType {
  allReportConfigs: BaseConfigType[];
  params?: ScheduleModalParams;
  closeModal: () => void;
  availableAccounts: AccountStateType | undefined;
  availableEmails: string[];
  showNotification: (x: any) => void;
  startSchedulesPoll: () => void;
  stopSchedulesPoll: () => void;
  headers: ReportsFetchHeaders;
  generatedBy: string;
  customConfigs?: CustomConfigType[];
  dashboardType: DashboardType;
  sessionMode?: string;
  parseSchedulePayloadBeforeSubmit: (x: ScheduleType, y: unknown) => ScheduleServerPayload;
}
