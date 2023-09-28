import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';

export type NewRoutes =
  | ROUTES_INFO.API_KEYS
  | ROUTES_INFO.WEBHOOKS
  | ROUTES_INFO.WEBSITE_APP_SETTINGS;

export type NewRoutesWebsiteAppSettingsInterface = NewRoutes[];

export interface MapNewRoutesMetadataInterface {
  [route: string]: OldRoutesMetaInterface;
}

export interface OldRoutesMetaInterface {
  oldRoute: string;
}

export type OldAndNewRouteMapInterface = {
  [key in ROUTES_INFO]?: string;
};

type User = Record<string, unknown>;

interface WebsiteSectionDetailsDataInterface {
  loading: boolean;
  data: any;
  error: boolean | string[];
}

export interface WebsiteAndAppSettingsProps extends RouteComponentProps {
  user: User;
  websiteSectionDetailsData: WebsiteSectionDetailsDataInterface;
  fetchMerchantWebsiteDetails: () => unknown;
  fetchConnectedApplications: () => Promise<void>;
  fetchOauthConnectedApplications: () => Promise<void>;
  shouldShowApplications: boolean;
  applications: {
    hasConnectedApplications: boolean;
    connectedAppsloading: boolean;
  };
}

export interface APIKeysProps extends RouteComponentProps {
  user: User;
  fetchAddWebsiteWorkflowStatus: () => Promise<{ data: any }>;
}

export interface BusinessWebsiteDetailsProps extends RouteComponentProps {
  user: User;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  workflows: Record<string, any>;
  fetchWorkflowStatus: (data: unknown) => unknown;
  openModal: (data?: unknown) => unknown;
  closeModal: () => unknown;
  isFlowRevamped: boolean;
}
