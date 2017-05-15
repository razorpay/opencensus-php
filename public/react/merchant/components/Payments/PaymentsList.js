import Time from 'rzp/ui/Time';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import TableBody from '../TableBody';
import { NavLink } from 'react-router-dom';

const PaymentsListItem = ({ payment, hasOrders, orders }) => {
  return (
    <tr>
      <td>
        <NavLink to={`/app/payments/${payment.id}`}>
          {payment.id}
        </NavLink>
      </td>
      {hasOrders && <td>{orders[payment.id]}</td>}
      <td>{payment.currency}</td>
      <td>{payment.amountInINR}</td>
      <td>{payment.email}</td>
      <td>{payment.contact}</td>
      <td>
        <Time value={payment.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
      <td>
        <PaymentStatusLabel status={payment.status} />
      </td>
    </tr>
  );
};

export default ({ payments, isLoading, hasOrders, orders }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Payment Id</th>
            {hasOrders && <th>Order Id</th>}
            <th>Currency</th>
            <th>Amount (INR)</th>
            <th>Customer Email</th>
            <th>Contact</th>
            <th>Created At</th>
            <th>Status</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={hasOrders ? 8 : 7}
          rows={payments}
          emptyTableMsg="No Payments found!"
        >
          {payments.map(payment => (
            <PaymentsListItem
              key={payment.id}
              payment={payment}
              hasOrders={hasOrders}
              orders={orders}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
