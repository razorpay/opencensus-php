import { DashboardType } from 'merchant_common/views/Reports/types';
import { ConfirmModalParams } from './components/ConfirmModal/types';
import { ScheduleType } from 'merchant_common/views/Reports/types/schedule';

export enum ReportModalTypes {
  'download_report' = 'download_report',
  'download_custom_report' = 'download_custom_report',
  'confirm_modal' = 'confirm_modal',
  'create_edit_schedule' = 'create_edit_schedule',
}

export type ReportModalType = keyof typeof ReportModalTypes;

export interface BaseReportModalPropsType {
  type: ReportModalType;
  onCloseCallback?: () => void;
  params?: {
    selectedConfig?: string;
    selectedLog?: string;
    selectedSchedule?: string;
    startPollOnSubmit?: boolean;
    scheduleData?: ScheduleType;
  } & ConfirmModalParams;
  dashboardType: DashboardType;
  ariaLabelBy?: string;
}

export interface ModalProps extends BaseReportModalPropsType {
  closeModal: () => void;
}

interface OtherModalConfig {
  scrollable?: boolean;
  initialWidth?: number;
}

export type RenderModalFnType = (
  children: JSX.Element,
  otherConfig?: OtherModalConfig,
) => JSX.Element;
