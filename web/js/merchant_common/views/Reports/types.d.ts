import { User } from 'common/typings';
import { SpiltzContextState } from 'common/splitz/types';

import { AccountStateType } from './types/account';
import { BaseConfigType } from './types/config';
import { ScheduleType } from './types/schedule';
import { OrgData } from 'newAuth/signin/types';

export enum Dashboard {
  partner = 'partner',
  merchant = 'merchant',
  linkedAccount = 'linkedAccount',
}

export type DashboardType = keyof typeof Dashboard;

export enum Mode {
  test = 'test',
  live = 'live',
}

export type ModeType = keyof typeof Mode;

export type CustomConfigType = BaseConfigType & {
  helpInfo?: {
    info: string;
    link: {
      label: string;
      href: string;
    };
  };
};

export interface RefDashboardConfigType {
  /**
   * Base path of the dashboard route.
   */
  basePath: string;
  /**
   * API headers specific to the dashboard type.
   */
  headers: Record<string, string>;
  /**
   * Additional report configs added apart from the ones from the BE API call specific to the dashboard type.
   */
  customConfigs: CustomConfigType[];
  availableAccounts: AccountStateType | undefined;
  availableFormats: Format[];
  /**
   * A parse fn to transform the report configs payload as defined in the config wrt the dashboard type.
   */
  parseConfigs: (x: BaseConfigType[]) => BaseConfigType[];
  parseSchedules: (x: ScheduleServerPayload[]) => ScheduleType[];
  /**
   * A parse fn to transform final payload of download report modal before submit wrt the specified dashboard.
   */
  parsePayloadBeforeSubmit: (x?, y?) => unknown;
  parseSchedulePayloadBeforeSubmit: (payload: ScheduleType) => ScheduleServerPayload;
}

export interface ReportSectionProps {
  user: User;
  allReportConfigs: BaseConfigType[];
  refDashboardConfig: RefDashboardConfigType;
  dashboardType: DashboardType;
  handleOverviewLoading: (x: { key: string; state: boolean }) => void;
  fetchReportsConfigsSuccess: (x: { configs: BaseConfigType[] }) => void;
  fetchReportsConfigsFailed: () => void;
  showNotification: (x: unknown) => void;
  fetchAccounts: () => Promise<void>;
  org?: OrgData;
  splitz: SpiltzContextState;
}

export interface ReportsPropType {
  /**
   * Dashboard type where reports ui is to be used. (partner | merchant | linkedAccount).
   */
  dashboard: DashboardType;
}

export interface Format {
  label: string;
  value: string;
}

export type Delimiter = Format;

export interface QueryStringParams {
  title?: string;
  viewType: string;
  skip?: number;
  count?: number;
}

export interface fetchReportingConfigProps {
  headers: Record<string, string>;
  allReportConfigs?: BaseConfigType[];
  dashboardType: DashboardType;
  handleOverviewLoading: (x: { key: string; state: boolean }) => void;
  fetchReportsConfigsSuccess: (x: { configs: BaseConfigType[] }) => void;
  fetchReportsConfigsFailed: () => void;
  parseConfigs?: (x: BaseConfigType[]) => BaseConfigType[];
}
