import { User } from 'common/typings';

export interface LoaderSize {
  width: string;
  height: string;
}

export interface LoaderPropsType {
  height?: number | string;
  width?: number | string;
  makePxValue?: boolean;
  padding?: number[] | string[] | number | string;
  margin?: number[] | string[] | number | string;
}

export interface TextWrapperPropTypes {
  text: string;
  className?: string;
  fontSize?: string;
  color?: string;
  fontWeight?: number | string;
  mobileFontSize?: string;
  mobileFontWeight?: string;
}

export interface OnboardingPropTypes {
  openModal: any;
  closeModal: any;
  handleInfo: any;
  setOnboardingVisible: (isOnboardingVisible: boolean) => void;
}

export interface PaymentPageEntity {
  id: string;
  amount: number | null;
  currency: string;
  currency_symbol: string;
  expire_by: null;
  times_payable: null;
  times_paid: number;
  total_amount_paid: number;
  status: string;
  status_reason: null;
  short_url: string;
  user_id: null;
  user: null;
  receipt: null;
  title: string;
  description: null;
  notes: any[];
  support_contact: null;
  support_email: null;
  terms: null;
  type: string;
  payment_page_items: {
    id: string;
    entity: string;
    payment_link_id: string;
    item: {
      id: string;
      active: boolean;
      name: string;
      description: null;
      amount: null;
      unit_amount: null;
      currency: string;
      type: string;
      unit: null;
      tax_inclusive: boolean;
      hsn_code: null;
      sac_code: null;
      tax_rate: null;
      tax_id: null;
      tax_group_id: null;
      created_at: number;
    };
    mandatory: boolean;
    image_url: null;
    stock: null;
    quantity_sold: number;
    total_amount_paid: number;
    min_purchase: null;
    max_purchase: null;
    min_amount: number;
    max_amount: null;
    settings: {
      position: string;
    };
    plan_id: null;
    product_config: null;
  }[];
  created_at: number;
  updated_at: number;
  slug: string;
  captured_payments_count: number;
  settings: {
    udf_schema: string;
    version: string;
    theme: string;
  };
}

export interface BannerProps {
  handleInfo: PaymentHandleType;
  isMobile: boolean;
  isTestMode: boolean;
  closeModal: () => void;
  openModal: (payload: any) => void;
}

export interface ListFilterPropTypes {
  handleInfo: PaymentHandleType;
  isTestMode: boolean;
  paymentPageEntity: PaymentPageEntity;
}

export interface PaymentHandleListFilterPropTypes {
  user: User;
  mode: string;
  isMobile: boolean;
  handleInfo: HandleInfoTypes;
  fetchPaymentHandle: any;
}
interface showNotificationArgs {
  type: 'error' | 'success';
  message: string;
  hidePrevious?: boolean;
}

export type ShowNotificationT = (arg: showNotificationArgs) => void;

interface PaymentHandleType {
  id: string;
  slug: string;
  title: string;
  url: string;
}

export interface HandleInfoTypes {
  data: PaymentHandleType;
  error: any;
  loading: boolean;
}
export interface PaymentHandleIndexPropTypes {
  user: any;
  handleInfo: HandleInfoTypes;
  showNotification: ShowNotificationT;
  fetchPaymentHandle: any;
  createPaymentHandle: any;
}

export interface EditSlugModalPropTypes {
  handleInfo: PaymentHandleType;
  isBottomSheet: any;
  onCancelClick: () => void;
  showNotification: ShowNotificationT;
  fetchPaymentHandle: any;
}
