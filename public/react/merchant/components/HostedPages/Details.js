import Amount from 'rzp/ui/Amount';
import TableBody from '../TableBody';
import { HostedPaymentStatusLabel } from 'merchant/components/StatusLabel';

const HostedPagesListItem = ({ item }) => {
  let merchantHeaders = Object.keys(item.value.merchant_fields || {});
  return (
    <tr>
      <td>{item.value.uid}</td>
      <td>{item.value.id}</td>
      <td>
        <Amount value={item.value.amount} />
      </td>
      {merchantHeaders.map(header => (
        <td>{item.value.merchant_fields[header]}</td>
      ))}
      <td class="text-right">
        <HostedPaymentStatusLabel
          status={item.payment_count ? 'paid' : 'pending'}
        />
      </td>
    </tr>
  );
};

export default ({ hostedpage, isLoading }) => {
  let merchantHeaders = Object.keys(
    hostedpage.items[0].value.merchant_fields || {}
  );
  let headersCount = merchantHeaders + 3;

  return (
    <div class="table-responsive">
      <table class="table table-hover">
        <thead>
          <tr>
            <th>UID</th>
            <th>ID</th>
            <th>Amount</th>
            {merchantHeaders.map(header => <th>{header}</th>)}
            <th class="text-right">Status</th>
          </tr>
        </thead>
        <TableBody
          isLoading={isLoading}
          colSpan={headersCount}
          rows={hostedpage.items}
          emptyTableMsg="No Hosted Pages found!"
        >
          {hostedpage.items.map(item => (
            <HostedPagesListItem key={item.value.id} item={item} />
          ))}
        </TableBody>
      </table>
    </div>
  );
};
