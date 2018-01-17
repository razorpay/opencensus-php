import DataTable from 'rzp/ui/Table/DataTable';
import { Link } from 'react-router-dom';

import {
  paymentId,
  paymentOrder,
  rzpPaymentOrder,
  amount,
  email,
  contact,
  createdAt,
  status,
} from 'rzp/ui/item/pair';

import rowClass from 'merchant/utils/activeRow';

const getOrderId = ({ notes }) => {
  // Merchant's custom defined order IDs
  // First, we look for whole match. If that fails, we try `order_id` suffix
  var orderId = notes.order_id || notes.orderId;
  if (orderId) {
    return orderId;
  }
  for (let key in notes) {
    if (key.endsWith('_order_id')) {
      return notes[key];
    }
  }

  return null;
};

const getRazorpayOrderId = ({ order_id }) => {
  if (order_id) {
    return (
      <Link to={`/orders/${order_id}`}>
        <code>{order_id}</code>
      </Link>
    );
  }

  return null;
};

const mapOrders = payments =>
  payments.reduce((orders, payment) => {
    let orderId = getOrderId(payment);
    if (orderId) {
      orders[payment.id] = orderId;
    }
    return orders;
  }, {});

const mapRzpOrders = payments =>
  payments.reduce((orders, payment) => {
    let razorpayOrderId = getRazorpayOrderId(payment);
    if (razorpayOrderId) {
      orders[payment.id] = razorpayOrderId;
    }
    return orders;
  }, {});

export default props => {
  let paymentColumns = [paymentId, amount, email, contact, createdAt, status];

  let orders = mapOrders(props.items);
  let rzpOrders = mapRzpOrders(props.items);

  // if there is atleast one visible "order-id"
  if (Object.keys(orders).length) {
    paymentColumns.splice(1, 0, paymentOrder(orders));
  }

  // if there is atleast one visible Razorpay's "order_id"
  if (Object.keys(rzpOrders).length) {
    paymentColumns.splice(1, 0, rzpPaymentOrder(rzpOrders));
  }

  return <DataTable title="Payments" columns={paymentColumns} {...props} />;
};
