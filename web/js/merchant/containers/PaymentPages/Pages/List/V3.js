import { NavLink } from 'react-router-dom';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'rzp/ui/Amount';
import TableBody from 'rzp/ui/TableBody';
import Time from 'rzp/ui/Time';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';

import { trackListActions } from '../ga';

// import dummyPaymentPages from '../dummy_paymentpages';

export default ({ paymentPages, loading }) => {
  // paymentPages = dummyPaymentPages;

  return (
    <div class="table-responsive Table--PaymentpagesV3">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Title</th>
            <th>Total Sales</th>
            <th>Item Name</th>
            <th>Quantity Sold</th>
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
                  to={`/paymentpages/${item.id}/payments`}
                  onClick={() => {
                    trackListActions('Title Click');
                  }}
                >
                  {item.title}
                </NavLink>
              </td>

              {/* TODO: Check if needed to be manually calculated from items or we've direct value */}
              <td>
                <Amount
                  value={item.total_amount_paid}
                  currency={item.currency}
                />
              </td>

              <td>
                <table>
                  <tbody>
                    {item.payment_page_items.slice(0, 2).map((pi, ix) => (
                      <tr key={ix}>
                        <td>
                          <span class="item-ellipsis">{pi.item.title}</span>
                        </td>
                      </tr>
                    ))}
                    {item.payment_page_items.length > 2 && (
                      <tr>
                        <td>
                          <span class="more-btn">
                            <b>+ {item.payment_page_items.length - 2} more</b>
                          </span>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </table>
              </td>

              <td>
                <table>
                  <tbody>
                    {item.payment_page_items.slice(0, 2).map((pi, ix) => (
                      <tr key={ix}>
                        <td>
                          {Number(pi.times_paid)}
                          {!!pi.quantity_available && (
                            <span style={{ opacity: 0.7 }}>
                              {' '}
                              of {Number(pi.quantity_available)}
                            </span>
                          )}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
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
