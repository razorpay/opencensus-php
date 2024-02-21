import { ListApiParams } from 'merchant/views/GCMS/shared/types';

export enum OrderDeliveryStatusEnum {
  DELIVERY_IN_PROGRESS = 'delivery_in_progress',
  DELIVERY_COMPLETED = 'completed',
}

export enum OrderDeliveryStatusTypeEnum {
  INITIATED = 'initiated',
  SUCCESS = 'success',
  FAILED = 'failed',
}

export enum OrderStatusEnum {
  DRAFT = 'draft',
  SUBMITTED = 'submitted',
  PROCESSING = 'processing',
  PROCESSED = 'prcoessed',
  CANCELLED = 'cancelled',
}

export type Order = {
  id: string;
  updated_at: string;
  created_at: string;
  merchant_id?: string;
  is_multiple_delivery?: boolean;
  total_amount?: number;
  status: OrderStatusEnum;
  order_items?: OrderItem[];
  reseller_id?: string;
  total_quantity?: number;
  net_amount?: number;
  issued_quantity?: number;
  processed_quantity?: number;
  delivery_status?: OrderDeliveryStatusEnum;
  reseller_detail_id?: string;
};

export type OrderDeliveryStatus = {
  total_giftcard_count: number;
  items: OrderDeliveryStatusItem[];
};

export type OrderItem = {
  program_id?: string;
  sku_id?: string;
  quantity?: number;
  issued_quantity?: number;
  denomination?: number;
  total_amount?: number;
  net_amount?: number;
  tax?: number;
  fee?: number;
  discount_percent?: number;
};

export type OrderDeliveryStatusItem = {
  status: OrderDeliveryStatusTypeEnum;
  count: number;
};

export type OrderDeliveryBatchItem = {
  id: string;
  emails: number;
};

export type OrderDeliveryBatch = {
  order_id: string;
  total_emails_uploaded: number;
  batches: OrderDeliveryBatchItem[];
};

export type OrderDeliveryBatchDetails = {
  id: string;
  name: string;
};

export interface OrderCreateParams extends ListApiParams {
  orderId?: string;
  merchantId?: string;
  order?: Order;
}

export interface OrderUpdateParams extends OrderCreateParams {
  status?: string;
}

export interface OrderSubmitParams extends ListApiParams {
  orderId: string;
  merchantId: string;
}

export interface OrderItemsCreateParams extends ListApiParams {
  orderId: string;
  merchantId: string;
  orderItems: OrderItem[];
}

export interface OrderItemPatchParams extends ListApiParams {
  itemId: string;
  orderId: string;
  merchantId: string;
  orderItem: OrderItem;
}

export interface OrderItemDeleteParams extends ListApiParams {
  itemId: string;
  orderId: string;
  merchantId: string;
}

export interface OrderItemDenomination extends OrderItem {
  index?: number;
  id?: string;
  type?: string;
}
