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
} from 'common/ui/item/pair';
import EntityTable from 'merchant/components/EntityTable';
import PaymentOptimizerProvider from 'merchant/views/Transactions/Payments/components/PaymentOptimizerProvider';

const getOrderId = ({ notes }) => {
  // Merchant's custom defined order IDs
  // First, we look for whole match. If that fails, we try `order_id` suffix
  if (!notes) return null;

  const orderId = notes.order_id || notes.orderId;
  if (orderId) {
    return orderId;
  }
  for (const key in notes) {
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

const mapOrders = (payments) =>
  payments.reduce((orders, payment) => {
    const orderId = getOrderId(payment);
    if (orderId) {
      orders[payment.id] = orderId;
    }
    return orders;
  }, {});

const mapRzpOrders = (payments) =>
  payments.reduce((orders, payment) => {
    const razorpayOrderId = getRazorpayOrderId(payment);
    if (razorpayOrderId) {
      orders[payment.id] = razorpayOrderId;
    }
    return orders;
  }, {});

export default (props) => {
  let paymentColumns = [paymentId, amount, email, contact, createdAt, status];

  if (props.paymentColumns) {
    paymentColumns = props.paymentColumns;
  }

  if (props.user?.isSingleReconEnabled && props.user?.isOptimizerEnabled) {
    paymentColumns.splice(1, 0, {
      title: 'Payment Provider',
      value: (item) => (
        <PaymentOptimizerProvider
          terminal_id={item.optimizer_provider}
          settled_by={item.settled_by}
          terminalProviders={props.terminalProviders}
          hideExternalLink={true}
          isTableView={true}
        />
      ),
    });
  }

  const orders = mapOrders(props.items);
  const rzpOrders = mapRzpOrders(props.items);

  // if there is at least one visible "order-id"
  if (Object.keys(orders).length) {
    paymentColumns.splice(1, 0, paymentOrder(orders));
  }

  // if there is at least one visible Razorpay's "order_id"
  if (Object.keys(rzpOrders).length) {
    paymentColumns.splice(1, 0, rzpPaymentOrder(rzpOrders));
  }

  return <EntityTable title="Payments" columns={paymentColumns} {...props} />;
};
