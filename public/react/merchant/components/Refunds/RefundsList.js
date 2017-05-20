import Time from 'rzp/ui/Time';
import TableBody from '../TableBody';
import TransactionNavLink from 'merchant/components/TransactionNavLink';

const RefundsListItem = ({ refund, onRefundClick }) => {
  return (
    <tr>
      <td>
        <TransactionNavLink
          to={`/app/refunds/${refund.id}`}
          onClick={() => onRefundClick(refund)}
        >
          {refund.id}
        </TransactionNavLink>
      </td>
      <td>
        <a target="_blank" href={`#/app/payments/${refund.payment_id}`}>
          {refund.payment_id}
        </a>
      </td>
      <td>{refund.currency}</td>
      <td>{refund.amountInINR}</td>
      <td>
        <Time value={refund.created_at} format="DD MMM YYYY, hh:mm:ss a" />
      </td>
    </tr>
  );
};

export default ({ refunds, isLoading, onRefundClick }) => {
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
            <RefundsListItem
              key={refund.id}
              refund={refund}
              onRefundClick={onRefundClick}
            />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
