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
import PaymentOptimizerProvider from 'merchant/views/Transactions/v1/Payments/components/PaymentOptimizerProvider';
import { selfServerTrack } from 'merchant/views/Transactions/v1/AnalyticsTrack';
import { makeIdLink } from 'merchant/views/Transactions/v1/Payments/Utils';
import { selfServeTrackInitiate } from 'common/utils/selfServeAnalytics';

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

const getRazorpayOrderId = ({ order_id }, initiatePage) => {
  const screen = initiatePage?.split('.')[0] || 'Transactions';
  const page = initiatePage?.split('.')[1];

  if (order_id) {
    const selfServeInitiateData = {
      selfServeAction: 'Order Details Fetched',
      page,
      screen,
      props: {
        initiatePoint: 'payments-table',
      },
    };

    if (window?.session_id) selfServeInitiateData.props.sessionId = window.session_id;
    return (
      <Link
        to={`/orders/${order_id}?init_point=payments-table&init_page=${initiatePage}`}
        onClick={() => {
          selfServeTrackInitiate(selfServeInitiateData);
        }}
      >
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

const mapRzpOrders = (payments, initiatePage) =>
  payments.reduce((orders, payment) => {
    const razorpayOrderId = getRazorpayOrderId(payment, initiatePage);
    if (razorpayOrderId) {
      orders[payment.id] = razorpayOrderId;
    }
    return orders;
  }, {});

const _paymentId = (initiatePage) => {
  return {
    title: paymentId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('payment')(item, initiatePage);
      return <div>{intermediateElement}</div>;
    },
  };
};

export default (props) => {
  const { selfServeActionsPage, isJkOrg } = props;
  let paymentColumns = [
    _paymentId(selfServeActionsPage),
    amount,
    email,
    contact,
    createdAt,
    status,
  ];

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
  const rzpOrders = mapRzpOrders(props.items, selfServeActionsPage);

  // if there is at least one visible "order-id"
  if (Object.keys(orders).length && !isJkOrg) {
    paymentColumns.splice(1, 0, paymentOrder(orders));
  }

  // if there is at least one visible Razorpay's "order_id"
  if (Object.keys(rzpOrders).length) {
    paymentColumns.splice(1, 0, rzpPaymentOrder(rzpOrders));
  }

  return (
    <EntityTable
      title="Payments"
      columns={paymentColumns}
      onCellClick={selfServerTrack}
      {...props}
    />
  );
};
