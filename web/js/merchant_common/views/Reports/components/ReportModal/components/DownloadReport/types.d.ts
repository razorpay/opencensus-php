import { ShowNotificationType } from 'common/typings';
import { AccountStateType } from 'merchant_common/views/Reports/Types/account';
import { BaseConfigType } from 'merchant_common/views/Reports/Types/config';
import { CustomConfigType, DashboardType } from 'merchant_common/views/Reports/types';
import moment from 'moment';

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
  headers: Record<string, string>;
  generatedBy: string;
  customConfigs?: CustomConfigType[];
  sessionMode?: string;
  parsePayloadBeforeSubmit: (x: BaseLogPayloadType, y: unknown) => BaseLogPayloadType;
  handlePageChange: (x: number) => void;
  dashboardType: DashboardType;
  resetLogsPollOnSubmit?: boolean;
}
