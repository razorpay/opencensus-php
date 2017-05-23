import Time from 'rzp/ui/Time';
import TableBody from '../TableBody';
import { NavLink } from 'react-router-dom';

const RefundsListItem = ({ refund }) => {
  return (
    <tr>
      <td>
        <NavLink to={`/refunds/${refund.id}`}>
          <code>{refund.id}</code>
        </NavLink>
      </td>
      <td>
        <NavLink to={`/payments/${refund.payment_id}`}>
          <code>{refund.payment_id}</code>
        </NavLink>
      </td>
      <td>{refund.currency}</td>
      <td>{refund.amountInINR}</td>
      <td>
        <Time value={refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
    </tr>
  );
};

export default ({ refunds, isLoading }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>Refund Id</th>
            <th>Payment Id</th>
            <th>Currency</th>
            <th>Amount (INR)</th>
            <th>Created At</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={5}
          rows={refunds}
          emptyTableMsg="No Refunds found!"
        >
          {refunds.map(refund => (
            <RefundsListItem key={refund.id} refund={refund} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
