import { NavLink } from 'react-router-dom';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Popover, { PopoverBody } from 'common/ui/Popover';

import { trackListActions } from '../ga';
import track from './track';

// import mockPaymentPagesList from './data-mock';

export default ({ paymentPages, loading }) => {
  // paymentPages = mockPaymentPagesList;

  return (
    <div class="table-responsive Table--PaymentpagesV3">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Title</th>
            <th>Total Sales</th>
            <th>Item Name</th>
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
          {paymentPages.map((item) => (
            <EntityItemRow id={item.id} key={item.id}>
              <td>
                <NavLink
                  to={`/paymentpages/${item.id}/payments#paymentpages`}
                  onClick={() => {
                    trackListActions('Title Click');
                  }}
                >
                  {item.title}
                </NavLink>
              </td>

              {/* TODO: Check if needed to be manually calculated from items or we've direct value */}
              <td>
                <Amount value={item.total_amount_paid} currency={item.currency} />
              </td>

              <td>
                <table>
                  <tbody>
                    {item.payment_page_items.slice(0, 2).map((pi, ix) => (
                      <tr key={ix}>
                        <td>
                          <span class="item-ellipsis">{pi.item.name}</span>
                        </td>
                      </tr>
                    ))}
                    {item.payment_page_items.length > 2 && (
                      <tr>
                        <td>
                          <span class="help-content">
                            <span class="more-btn">
                              <b>+ {item.payment_page_items.length - 2} more</b>
                            </span>

                            <Popover>
                              <PopoverBody>
                                <div class="more-items">
                                  <div>
                                    <span class="title">Item Name</span>

                                    {item.payment_page_items.slice(2).map((pi, ix) => (
                                      <span class="item-ellipsis" key={ix}>
                                        {pi.item.name}
                                      </span>
                                    ))}
                                  </div>
                                  <div>
                                    <span class="title">Quantities Sold</span>

                                    {item.payment_page_items.slice(2).map((pi, ix) => (
                                      <span class="item-ellipsis" key={ix}>
                                        {Number(pi.quantity_sold)}
                                        {!!pi.stock && (
                                          <span style={{ opacity: 0.7 }}>
                                            {' '}
                                            of {Number(pi.stock)}
                                          </span>
                                        )}
                                      </span>
                                    ))}
                                  </div>
                                </div>
                              </PopoverBody>
                            </Popover>
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
                          <span class="item-ellipsis">
                            {Number(pi.quantity_sold)}
                            {!!pi.stock && (
                              <span style={{ opacity: 0.7 }}> of {Number(pi.stock)}</span>
                            )}
                          </span>
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
                        track.copyUrl();
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
