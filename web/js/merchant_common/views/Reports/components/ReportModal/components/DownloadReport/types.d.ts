import moment from 'moment';

import { ShowNotificationType } from 'common/typings';

import { AccountStateType } from 'merchant_common/views/Reports/Types/account';
import { BaseConfigType } from 'merchant_common/views/Reports/Types/config';
import {
  CustomConfigType,
  DashboardType,
  Delimiter,
  Format,
} from 'merchant_common/views/Reports/types';
import { SelectedRangeType } from 'merchant_common/views/Reports/components/DateTimeRangePicker/types';

export interface PredefinedDurationType {
  label: string;
  value: {
    startDate: moment.Moment;
    endDate: moment.Moment;
  };
}

export interface DownloadReportModalPropsType {
  allReportConfigs: BaseConfigType[];
  params?: {
    selectedConfig?: string;
  };
  closeModal: () => void;
  availableAccounts: AccountStateType | undefined;
  availableEmails: string[];
  showNotification: ShowNotificationType;
  startLogsPoll: () => void;
  stopLogsPoll: () => void;
  headers?: Record<string, string>;
  defaultHeaders: Record<string, string>;
  generatedBy: string;
  customConfigs?: CustomConfigType[];
  sessionMode?: string;
  parsePayloadBeforeSubmit: (x: BaseLogPayloadType, y: unknown) => BaseLogPayloadType;
  handlePageChange: (x: number) => void;
  dashboardType: DashboardType;
  resetLogsPollOnSubmit?: boolean;
  availableFormats: Format[];
}

// Batch Payment Pages Section.
export type BatchPage = {
  id: string;
  title: string;
  created_at?: number;
};

export type PaymentStatus = {
  label: string;
  value: string;
};

export type BatchId = PaymentStatus;

export type GeneratePayloadParams = {
  selectedConfig?: BaseConfigType;
  saveReportAs?: string;
  selectedFormat?: Format;
  selectedDelimiter?: Delimiter;
  selectedBatchPage?: BatchPage;
  selectedBatchIds?: BatchId[];
  selectedPaymentStatus?: PaymentStatus[];
  customDurationRange?: SelectedRangeType;
  selectedPredefinedDurationRange?: PredefinedDurationType;
  recipients?: string[];
  isCustomDurationEnabled: boolean;
  isRecipientsEnabled?: boolean;
};

export type ReportSectionValidParams = {
  selectedConfig?: BaseConfigType;
  selectedFormat?: Format;
  selectedDelimiter?: Delimiter;
};

export type DurationSectionValidParams = {
  selectedConfig?: BaseConfigType;
  customDurationRange?: SelectedRangeType;
  selectedPredefinedDurationRange?: PredefinedDurationType;
  isCustomDurationEnabled: boolean;
};

export type BatchSectionValidParams = {
  selectedConfig?: BaseConfigType;
  selectedBatchPage?: BatchPage;
  selectedBatchIds: BatchId[];
  batchIds: BatchId[];
  selectedPaymentStatus: PaymentStatus[];
};
