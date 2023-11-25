import { Store } from 'common/typings';

export interface GSTDetailsProps {
  fetchGST: () => void;
  merchant_gst: { gstin?: string };
  rzp_gst: { gstin: string };
  user: Store['session']['user'];
  showNotification: (args: { type: string; message: string }) => void;
}

export enum AlertType {
  SUCCESS = 'success',
  FAILURE = 'failure',
}

export interface AlertStatus {
  type: AlertType;
  gstIN?: string;
}

export interface HeaderProps {
  gstList: string[];
  defaultGSTIn?: string;
  setAlertStatus: (status: AlertStatus) => void;
  openModal: (args: {
    size: string;
    component: JSX.Element;
    isNew?: boolean;
    queryParams?: Record<string, string>;
  }) => void;
}

export interface UpdateModalProps {
  fetchGST: () => void;
  closeModal: () => void;
  gstList: string[];
  defaultGSTIn?: string;
  setAlertStatus: (status: AlertStatus) => void;
}

export interface Track {
  objectName: string;
  actionName?: string;
  screen?: string;
  properties?: Record<string, unknown>;
}
