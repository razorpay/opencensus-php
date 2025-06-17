export type CheckoutValidationError = {
  type: string;
  sev: 0 | 1 | 2;
  isHideCheckout: boolean;
  title?: string;
  description: string;
};

type UseLatestOrder = {
  latestOrder: OrderDetailsItem | undefined;
  isLatestOrderLoading: boolean;
  latestOrderFetchError: CheckoutValidationError | null;
  refetchLatestOrder: () => void;
};
export interface ApiResponse<T> {
  status_code: number;
  success: boolean;
  data?: T;
  errors?: string[];
}
export type OrderObjectType = {
  id: string;
  amount: number;
  amount_paid: number;
  amount_due: number;
  currency: string;
  status: string;
  created_at: number;
};
export type CreateOrderPayload = {
  delivery_address: {
    name: string;
    address: string;
    city: string;
    country: string;
    state: string;
    pin_code: string;
    phone_no: string;
  };
  items: {
    code: string;
    count: number;
    period: string;
  }[];
};

export interface OrderCreateResponse extends CreateOrderPayload {
  amount: {
    base: number;
    gst: number;
    total: number;
  };
  rental_amount: {
    base: number;
    gst: number;
    total: number;
  } | null;
}
export interface OrderDetailsItem extends OrderCreateResponse {
  id: string;
  order_id: string;
  merchant_id: string;
  created_at: number;
  arriving_at: number;
  delivered_at: number;
  rejected_at: number;
  rejection_reasons: {
    error_code: string;
    error_description: string;
    error_reason: string;
  } | null;
  status: 'paid' | 'delivered' | 'rejected';
  order?: OrderObjectType | null;
  sales_code?: string;
  payment?: {
    amount: number;
    status: string;
    refund_status: null | string;
    captured: boolean;
    created_at: number;
  } | null;
  refund?: {
    id: string;
    amount: number;
    payment_id: string;
    created_at: number;
    status: RefundStatusTypes;
    acquirer_data: Record<string, string>;
  } | null;
}
