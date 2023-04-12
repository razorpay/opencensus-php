import { DashboardType } from 'merchant_common/views/Reports/types';

export interface BaseReportModalPropsType {
  type: string;
  onCloseCallback?: () => void;
  params?:
    | {
        /**
         * `id` of the selected config.
         */
        selectedConfig?: string;
      }
    | Record<string, unknown>;
  dashboardType: DashboardType;
  ariaLabelBy?: string;
}

export interface ModalProps extends BaseReportModalPropsType {
  closeModal: () => void;
}
