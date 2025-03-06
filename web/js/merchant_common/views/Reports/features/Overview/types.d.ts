import { Location } from 'history';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { DashboardType, RefDashboardConfigType } from 'merchant_common/views/Reports/types';
import { OpenModalPayload, ShowNotificationType } from 'common/typings';
import { Filter } from 'merchant/views/PaymentMetrics/types';

export interface CardPropsType {
  data: BaseConfigType;
  isSchedulesEnabled: boolean;
  openModal: (x: OpenModalPayload) => void;
  isCustomConfig: boolean;
}

export interface OverViewPropsType {
  allReportConfigs: BaseConfigType[];
  location: Location;
  recentlyUsedReportConfigs: BaseConfigType[];
  isAllConfigLoaded: boolean;
  isRecentlyUsedConfigLoaded: boolean;
  fetchRecentlyUsedConfigsFailed: () => void;
  fetchRecentlyUsedConfigsSuccess: (x: { configs: BaseConfigType[] }) => void;
  handleOverviewLoading: (x: { key: string; state: boolean }) => void;
  refDashboardConfig: RefDashboardConfigType;
  showNotification: ShowNotificationType;
  dashboardType: DashboardType;
}

export type BaseDivType = {
  index?: number;
};
