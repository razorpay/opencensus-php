import AsyncButton from 'react-async-button';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ListGroupToggler from 'rzp/ui/ListGroupToggler';
import {
  OrderStatusLabel,
  PaymentStatusLabel,
} from 'merchant/components/StatusLabel';
import TableBody from 'merchant/components/TableBody';
import DetailRow from 'merchant/components/DetailRow';
import { NavLink } from 'react-router-dom';

const PaymentList = ({ payment }) => {
  return (
    <tr>
      <td>
        <NavLink to={`/app/payments/${payment.id}`}>
          {payment.id}
        </NavLink>
      </td>
      <td>
        <PaymentStatusLabel status={payment.status} />
      </td>
      <td class="text-right">
        <Time value={payment.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
    </tr>
  );
};

export default props => {
  let { order, payments, isLoading, statusMsg, onCloseClick } = props;

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading
        ? <div class="page-spinner-container">
            <Spinner />
          </div>
        : <div class="panel panel-default">
            <Alert type={statusMsg.type} message={statusMsg.message} />

            <div class="panel-heading">
              Order ID: <b>{order.id}</b>
              <button type="button" class="close" onClick={onCloseClick}>
                <i class="icon icon-close" />
              </button>
            </div>

            <div class="panel-body">
              <div class="list-group details-row-container">
                <DetailRow
                  label="Amount"
                  value={() => <Amount value={order.amount} />}
                />

                <DetailRow label="Currency" value={order.currency} />
                <DetailRow label="Attempts" value={order.attempts} />

                <DetailRow
                  label="Status"
                  value={() => <OrderStatusLabel status={order.status} />}
                />

                {order.attempts > 0
                  ? <ListGroupToggler
                      label="Payments"
                      onToggleClick={() => props.onTogglePayments(order)}
                    >
                      <table class="table table-hover table-striped">
                        <TableBody
                          colSpan={2}
                          isLoading={payments.loading}
                          rows={payments.items}
                        >
                          {payments.items.map(payment => (
                            <PaymentList key={payment.id} payment={payment} />
                          ))}
                        </TableBody>
                      </table>
                    </ListGroupToggler>
                  : <DetailRow label="Payments" value="No Payments" />}

                <DetailRow
                  label="Created At"
                  value={() => (
                    <Time
                      value={order.created_at}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />
                  )}
                />
              </div>
            </div>
          </div>}
    </div>
  );
};
