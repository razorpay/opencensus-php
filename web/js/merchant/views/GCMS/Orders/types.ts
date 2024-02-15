import { ListApiParams } from 'merchant/views/GCMS/shared/types';

export type Order = {
  id: string;
  updated_at: string;
  created_at: string;
  merchant_id?: string;
  is_multiple_delivery?: boolean;
  total_amount?: number;
  status: string;
  order_items?: OrderItem[];
  reseller_id?: string;
  total_quantity?: number;
  net_amount?: number;
  issued_quantity?: number;
  processed_quantity?: number;
  delivery_status?: string;
  reseller_detail_id?: string;
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
