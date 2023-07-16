import moment from 'moment';
import React from 'react';
import ActionToolbar from 'merchant/views/MagicCheckout/ShopifyOrderEditing/common/ActionToolbar';
import {
  DATE_FORMAT,
  ORDER_STATUS_COLOR_MAPPING,
  ORDER_STATUS,
  PAYMENT_STATUS_COLOR_MAPPING,
} from 'merchant/views/MagicCheckout/ShopifyOrderEditing/constants';
import Popover, { PopoverBody } from 'common/ui/Popover';

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
    if (item.closed) {
      return (
        <span className={`status-label ${ORDER_STATUS_COLOR_MAPPING[status]}`}>
          {status}
          <Popover>
            <PopoverBody style={{ textAlign: 'center' }}>This order is archived</PopoverBody>
          </Popover>
        </span>
      );
    }
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
  value: (order: any) => {
    const hasDisabledStatus =
      (order.fulfillment_status !== ORDER_STATUS.UNFULFILLED &&
        order.fulfillment_status !== ORDER_STATUS.ON_HOLD) ||
      order.payment_status === 'VOIDED' ||
      order.closed;
    return (
      <ActionToolbar
        disableAction={hasDisabledStatus}
        openOrderEditingModal={openOrderEditingModal}
        id={order.platform_order_id}
        display_id={order.display_name}
      />
    );
  },
});
