import Table from 'rzp/ui/Table/Index';
import {
  paymentId,
  paymentOrder,
  amount,
  email,
  contact,
  createdAt,
  status,
} from 'rzp/ui/Table/Column';

const getOrderId = ({ order_id, notes }) => {
  if (order_id) {
    return <Link to={`/orders/${order_id}`}><code>{order_id}</code></Link>;
  }

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
};

const mapOrders = payments =>
  payments.reduce((orders, payment) => {
    let orderId = getOrderId(payment);
    if (orderId) {
      orders[payment.id] = orderId;
    }
    return orders;
  }, {});

export default ({ items }) => {
  let paymentColumns = [paymentId, amount, email, contact, createdAt, status];

  let orders = mapOrders(items);

  // if there is atleast one visible "order-id"
  if (Object.keys(orders).length) {
    paymentColumns = [paymentId, paymentOrder(orders)].concat(
      paymentColumns.slice(2)
    );
  }

  return <Table rows={items} columns={paymentColumns} />;
};
