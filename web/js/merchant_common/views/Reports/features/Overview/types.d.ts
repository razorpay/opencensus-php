import { History, Location } from 'history';
import { BaseConfigType } from 'merchant_common/views/Reports/types/config';
import { DashboardType, RefDashboardConfigType } from 'merchant_common/views/Reports/types';
import { ShowNotificationType } from 'common/typings';

export interface CardPropsType {
  data: BaseConfigType;
  linkBasePath: string;
}

export interface OverViewPropsType {
  allReportConfigs: BaseConfigType[];
  history: History;
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
