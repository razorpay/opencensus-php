import { ReactNode } from 'react';

import { ShowNotificationType } from 'common/typings';

export type IntegrationModalPropsType = {
  stepTexts: Record<string, any>[];
  demoContainer: () => JSX.Element;
  modalIcon: string;
  demoInfoText: string;
  points: Array<any>;
  pointsHeader: string;
  demoVideoLink: string;
  onSavingAccountCreds: (creds: Record<string, string>) => void;
};

export type CredentialsFormPropsType = {
  setStep: (arg0: any) => any;
  onSavingAccountCreds: (creds: Record<string, string>) => void;
  isLoading: boolean;
};

export interface NavContentComponentProps {
  analyticsSettingsConfigs: Record<string, any> | null;
}

export type NavItemPropType = {
  id: string;
  title: string;
  activeTab: string;
  setActiveTab: (id: string) => void;
};

export type NavContentPropType = {
  id: string;
  className: string;
  activeTab: string;
  component: React.ComponentType<NavContentComponentProps>;
  analyticsSettingsConfigs: Record<string, any> | null;
};

export type AnalyticConfigsType = {
  isLoading: Record<string, boolean>;
  hasError: boolean;
  merchantAnalyticsConfig: Record<string, any> | null;
};

export type AnalyticsSettingsPropsType = {
  platform: string;
  fetchAnalyticsSettings: () => any;
  analyticsSettingsConfigs: AnalyticConfigsType;
  showNotification: ShowNotificationType;
  resetConfigs: () => void;
};

export type GoogleAnalyticsPropsTypes = {
  analyticsSettingsConfigs: Record<string, any>;
  deleteConfig: (id: string) => any;
  showNotification: ShowNotificationType;
  closeModal: () => void;
  addConfigs: (params: Record<string, string>) => any;
  saveEvents: (events: Record<string, boolean>, platform: string) => any;
  openModal: (options: { size: string; className: string; component: ReactNode }) => void;
};

export type GoogleAdsPropsTypes = {
  analyticsSettingsConfigs: Record<string, any>;
  deleteConfig: (id: string) => any;
  showNotification: ShowNotificationType;
  addConfigs: (params: Record<string, string>) => any;
  closeModal: () => void;
  saveEvents: (events: Record<string, boolean>, platform: string) => any;
  openModal: (options: { size: string; className: string; component: ReactNode }) => void;
};

export type FacebookAdsPropsTypes = {
  analyticsSettingsConfigs: Record<string, any>;
  deleteConfig: (id: string) => any;
  showNotification: ShowNotificationType;
  addConfigs: (params: Record<string, string>) => any;
  closeModal: () => void;
  saveEvents: (events: Record<string, boolean>, platform: string) => any;
  openModal: (options: { size: string; className: string; component: ReactNode }) => void;
};

export type DemoVideoPropsType = {
  infoText: string;
  demoVideoLink: string;
};

export type LinkAccountInfoContainerPropsType = {
  demoVideo: (props: DemoVideoPropsType) => JSX.Element;
  modalIcon: string;
  demoInfoText: string;
  demoVideoLink: string;
};

export type LinkAccountFormPropsType = {
  closeModal: () => void;
  step: number;
  setStep: () => void;
  stepTexts: Record<string, any>[];
  points: Array<JSX.Element>;
  pointsHeader: string;
  onSavingAccountCreds: () => void;
  showNotification: ShowNotificationType;
};

export type IntegrationPointsPropsType = {
  setStep: (arg0: any) => any;
  points: Array<JSX.Element>;
  pointsHeader: string;
};

export type ModalProp = {
  size: string;
  className: string;
  component: JSX.Element;
};

export type InputContainerPropsType = {
  tableHeader: string;
  headerIcon: string;
  customIntegrationOptions?: Array<Record<string, string>>;
  integrationModalProps: IntegrationModalPropsType;
  merchantAnalyticsConfigs: Record<string, any>;
  openModal: (arg0: ModalProp) => void;
  setIntegrationMethod: (e: React.ChangeEvent<HTMLInputElement>) => void;
  deleteAccountConfig: (id: string) => void;
  fetchOauthId: () => any;
  showNotification: ShowNotificationType;
  oAuthAccountConfigs: Record<string, any>;
};

export type ContainerHeadingPropType = {
  header: string;
  onAddAccount: () => void;
  isCtaDisabled: boolean;
};

export type ContainerContentPropsType = {
  tableHeader: string;
  headerIcon: string;
  customIntegrationOptions?: Array<Record<string, string>>;
  integrationModalProps: Record<string, any>;
  merchantAnalyticsConfigs: Record<string, any>;
  setIntegrationMethod: (e: React.ChangeEvent<HTMLInputElement>) => void;
  deleteAccountConfig: (id: string) => void;
  oAuthAccountConfigs?: Record<string, any>;
};

export type AnalyticsEventsPreviewPropsType = {
  analyticsEvents: Record<string, string>[];
  setShowPreviewMode: (arg: boolean) => void;
  eventConfigs: Record<string, boolean>;
};

export type AnalyticsEventsEditPropsType = {
  analyticsEvents: Record<string, string>[];
  eventConfigs: Record<string, boolean>;
  updateEventConfigs: (toggleState: boolean, keyName: string) => void;
  saveEventConfigs: () => void;
  isSaveEventsCtaDisabled: boolean;
  isLoading: boolean;
};

export type AnalyticsEventsPropsType = {
  analyticsEvents: Record<string, string>[];
  header: string;
  eventConfigs: Record<string, boolean>;
  updateEventConfigs: (toggleState: boolean, keyName: string) => void;
  magicAnalyticsConfigs: Record<string, any>;
  showPreviewMode: boolean;
  setShowPreviewMode: (arg: boolean) => void;
  saveEventConfigs: () => void;
  isSaveEventsCtaDisabled: boolean;
};
