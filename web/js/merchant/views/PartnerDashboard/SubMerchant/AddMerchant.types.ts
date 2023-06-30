import type { ActionCreator } from 'redux';
import type { SubmitHandler } from 'redux-form';
import type { RTrackingT } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';

export interface AddMerchantPropsT {
  closeModal: () => void;
  source?: string;
  referralData?: string | Record<string, unknown>;
  // TS_TODO: TS has a problem with redux connected props.
  user?: any;
  org: any;
  onAddSuccess?: () => void;
  addType?: string;
  isMobileResolution?: boolean;
  location?: Location;
  // TS_TODO: Replace any with payload object defined in reducers
  create?: ActionCreator<any>;
  showNotification?: ActionCreator<any>;
  createBatch?: ActionCreator<any>;
  createCapitalBatch?: ActionCreator<any>;
  validateBatch?: ActionCreator<any>;
  validateCapitalBatch?: ActionCreator<any>;
  createReferralInvitesBatch?: ActionCreator<any>;
  validateReferralInvitesBatch?: ActionCreator<any>;
  tracking?: RTrackingT;
  handleSubmit?: SubmitHandler;
}

export interface AddMerchantStateT {
  referralData: string | Record<string, unknown>;
  merchantType: string;
  step: number;
  file_id: string;
  addMode: string;
  bulkContactsCount: number;
  merchantName: string;
  merchantEmail: string;
  merchantContact: string;
  isFormValid: boolean;
}

export interface ReduxFormEvent extends Event {
  target: {
    addEventListener: (
      type: string,
      callback: EventListenerOrEventListenerObject,
      options?: boolean | AddEventListenerOptions,
    ) => void;
    dispatchEvent: (event: Event) => boolean;
    removeEventListener: (
      type: string,
      callback: EventListenerOrEventListenerObject,
      options?: boolean | EventListenerOptions,
    ) => void;
    name: string;
    value: string;
  };
}

export type NewMerchant = {
  name: string;
  email: string;
  contact_mobile: string;
  password: string;
};
