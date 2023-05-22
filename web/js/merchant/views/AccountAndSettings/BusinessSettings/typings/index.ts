import { CloseModalType, OpenModalType } from 'common/typings';
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
  page: string;
}

export interface BusinessSettingsProps extends RouteComponentProps {
  user: User;
  isMobile?: boolean;
}

export interface ApiResponse<T> {
  data: T;
  status: number;
  loading: boolean;
}

export interface SupportDetailsInterface {
  name: string;
  email: string;
  url: string;
  phone: string;
}

export interface OwnerDetailsInterface {
  contact_email: string;
  contact_name: string;
  contact_mobile: string;
}

export interface CustomerSupportDetailProps extends RouteComponentProps, OpenModalType {
  fetchSupportDetail: () => Promise<void>;
  support_detail: ApiResponse<SupportDetailsInterface>;
  closeModal: CloseModalType;
  openModal: OpenModalType;
}

export interface SubInfoDetails {
  label: string;
  type: string;
  showDisabledCTA: boolean;
}

export interface InfoDetails<T> extends SubInfoDetails {
  getValue: (args: T) => string;
}

export interface InfoDetailsPageProps extends SubInfoDetails {
  isEditEnable: boolean;
  value: string;
}

export interface DetailsViewCardProps {
  title: string;
  info: InfoDetailsPageProps[];
  handleAction?: (args: InfoDetailsPageProps) => void;
}

export interface QueryParamTriggerInterface {
  key: string;
  value: string;
  trigger: () => void;
}
