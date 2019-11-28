import { NavLink } from 'react-router-dom';

import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import CustomClipboard from 'common/ui/Clipboard/Custom';

import { trackListActions } from '../ga';

export default ({ paymentPages, loading }) => {
  return (
    <div class="table-responsive">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Title</th>
            <th>Amount</th>
            <th>Total Sales</th>
            <th>Units Sold</th>
            <th>Page Url</th>
            <th>Created On</th>
            <th>Status</th>
          </tr>
        </thead>
        <TableBody
          isLoading={loading}
          colSpan={8}
          rows={paymentPages}
          emptyTableMsg="No data found!"
        >
          {paymentPages.map(item => (
            <EntityItemRow id={item.id} key={item.id}>
              <td>
                <NavLink
                  to={`/paymentpages/${item.id}`}
                  onClick={() => {
                    trackListActions('Title Click');
                  }}
                >
                  {item.title}
                </NavLink>
              </td>
              <td class="text-right">
                {item.amount ? (
                  <Amount value={item.amount} currency={item.currency} />
                ) : (
                  '--'
                )}
              </td>

              <td class="text-right">
                <Amount
                  value={item.total_amount_paid}
                  currency={item.currency}
                />
              </td>

              <td>
                {Number(item.payment_page_items[0].quantity_sold)}
                {!!item.payment_page_items[0].stock && (
                  <span style={{ opacity: 0.7 }}>
                    {' '}
                    of {Number(item.payment_page_items[0].stock)}
                  </span>
                )}
              </td>

              <td>
                {item.short_url && (
                  <span class="CopyLink">
                    <span>{item.short_url}</span>
                    <CustomClipboard
                      value={item.short_url}
                      onCopy={() => {
                        trackListActions('Click Copy URL');
                      }}
                    >
                      <button class="btn btn-default btn-xs">copy</button>
                    </CustomClipboard>
                  </span>
                )}
              </td>
              <td>
                <Time value={item.created_at} />
              </td>
              <td>
                <PaymentPagesStatusLabel status={item.status} />
              </td>
            </EntityItemRow>
          ))}
        </TableBody>
      </table>
    </div>
  );
};
