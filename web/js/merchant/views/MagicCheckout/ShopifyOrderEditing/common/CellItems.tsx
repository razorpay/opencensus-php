import moment from 'moment';
import React from 'react';
import ActionToolbar from 'merchant/views/MagicCheckout/ShopifyOrderEditing/common/ActionToolbar';
import {
  DATE_FORMAT,
  ORDER_STATUS_COLOR_MAPPING,
  PAYMENT_STATUS_COLOR_MAPPING,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';

export const razorpayId = {
  title: 'Razorpay Order Id',
  value: (item: any) => item.id || '-',
};

export const shopifyOrderId = {
  title: 'Shopify Order Id',
  value: (item: any) => item.display_name || '-',
};

export const receiptId = {
  title: 'Receipt Id',
  value: (item: any) => item.display_name || '-',
};

export const date = {
  title: 'Created At',
  value: (item: any) => moment(item.created_at).format(DATE_FORMAT) || '-',
};

export const customerName = {
  title: 'Customer Name',
  value: (item: any) => item.customer || '-',
};

export const price = {
  title: 'Price',
  value: (item: any) => `₹ ${item.price / 100}` || '-',
};

export const orderStatus = {
  title: 'Fulfillment Status',
  value: (item: any) => {
    const status = item.fulfillment_status || '-';
    return <span className={`status-label ${ORDER_STATUS_COLOR_MAPPING[status]}`}>{status}</span>;
  },
};

export const paymentStatus = {
  title: 'Payment Status',
  value: (item: any) => {
    const status = item.payment_status || '-';
    return <span className={`status-label ${PAYMENT_STATUS_COLOR_MAPPING[status]}`}>{status}</span>;
  },
};

export const actions = (openOrderEditingModal: { (id: string, display_id: string): void }) => ({
  title: 'Action',
  columnClass: 'text-center',
  value: (order: any) => {
    const hasDisabledStatus = !order.is_editable;
    return (
      <ActionToolbar
        disableAction={hasDisabledStatus}
        openOrderEditingModal={openOrderEditingModal}
        id={order.platform_order_id}
        display_id={order.display_name}
        editable_errors={order.editable_errors}
      />
    );
  },
});
