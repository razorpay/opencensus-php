import { RouteComponentProps } from 'react-router-dom';

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type User = Record<string, any>;

export interface ContactDetailsProps extends RouteComponentProps {
  user: User;
  openModal: (data?: unknown) => unknown;
  closeModal: () => unknown;
  showNotification: (data: unknown) => unknown;
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  updateMerchantConfig: (data: unknown) => Promise<any>;
  updateSession: (data: unknown) => unknown;
  isFlowRevamped: boolean;
}

export interface BusinessSettingsProps extends RouteComponentProps {
  user: User;
}
