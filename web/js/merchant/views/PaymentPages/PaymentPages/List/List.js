import { NavLink } from 'react-router-dom';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import Amount from 'common/ui/Amount';
import TableBody from 'common/ui/TableBody';
import Time from 'common/ui/Time';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Popover, { PopoverBody } from 'common/ui/Popover';
import {
  getUnitsDescription,
  isBatchPaymentPages as fnIsBatchPaymentPages,
} from 'merchant/views/PaymentPages/PaymentPages/utils';
import ShowWhen from 'merchant/components/ShowWhen';

import { trackListActions } from 'merchant/views/PaymentPages/PaymentPages/ga';
import track from './track';

// import mockPaymentPagesList from './data-mock';

export default ({ paymentPages, loading, isStorefrontPage }) => {
  // paymentPages = mockPaymentPagesList;

  const trackCopyClick = () => {
    trackListActions('Click Copy URL');
    track.copyUrl();
  };

  const trackTitleClick = () => {
    trackListActions('Title Click');
  };

  const isBatchPaymentPages = fnIsBatchPaymentPages();

  return (
    <div class="table-responsive Table--PaymentpagesV3">
      <table class="table table-hover table-striped">
        <thead>
          <tr>
            <th>Title</th>
            <th>Total Sales</th>
            <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
              <th>Item Name</th>
              <th>Units Sold</th>
            </ShowWhen>
            <th>Page Url</th>
            <th>Created On</th>
            <th>Status</th>
            <ShowWhen additionalCondition={() => isBatchPaymentPages}>
              <th>Actions</th>
            </ShowWhen>
          </tr>
        </thead>
        <TableBody
          isLoading={loading}
          colSpan={8}
          rows={paymentPages}
          emptyTableMsg="No data found!"
        >
          {paymentPages.map((item) => {
            item.payment_page_items = item?.payment_page_items || [];
            return (
              <EntityItemRow id={item.id} key={item.id}>
                <td>
                  <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
                    <NavLink
                      to={`/paymentpages/${isStorefrontPage ? 'storefront/' : ''}${
                        item.id
                      }/payments${isStorefrontPage ? '#storefront' : '#paymentpages'}`}
                      onClick={trackTitleClick}
                    >
                      {item.title}
                    </NavLink>
                  </ShowWhen>
                  <ShowWhen additionalCondition={() => isBatchPaymentPages}>
                    <NavLink
                      to={`/paymentpages/batchpaymentpages/${item.id}/payments#batchpaymentpages`}
                      onClick={trackTitleClick}
                    >
                      {item.title}
                    </NavLink>
                  </ShowWhen>
                </td>

                {/* TODO: Check if needed to be manually calculated from items or we've direct value */}
                <td>
                  <Amount value={item.total_amount_paid} currency={item.currency} />
                </td>

                <ShowWhen additionalCondition={() => !isBatchPaymentPages}>
                  <td>
                    <table>
                      <tbody>
                        {item?.payment_page_items?.slice(0, 2).map((pi, ix) => (
                          <tr key={ix}>
                            <td>
                              <span class="item-ellipsis">{pi?.item?.name}</span>
                            </td>
                          </tr>
                        ))}
                        {item?.payment_page_items?.length > 2 && (
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

                                        {item?.payment_page_items?.slice(2).map((pi, ix) => (
                                          <span class="item-ellipsis" key={ix}>
                                            {pi?.item?.name}
                                          </span>
                                        ))}
                                      </div>
                                      <div>
                                        <span class="title">Quantities Sold</span>

                                        {item?.payment_page_items?.slice(2).map((pi, ix) => (
                                          <span class="item-ellipsis" key={ix}>
                                            {!isStorefrontPage ? (
                                              <>
                                                {Number(pi.quantity_sold)}
                                                {!!pi.stock && (
                                                  <span style={{ opacity: 0.7 }}>
                                                    {' '}
                                                    of {Number(pi.stock)}
                                                  </span>
                                                )}
                                              </>
                                            ) : (
                                              getUnitsDescription({
                                                units: pi.stock,
                                                quantitySold: pi.quantity_sold,
                                                status: pi.catalog_status,
                                              })
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
                        {item?.payment_page_items?.slice(0, 2).map((pi, ix) => (
                          <tr key={ix}>
                            <td>
                              <span class="item-ellipsis">
                                {!isStorefrontPage ? (
                                  <>
                                    {Number(pi.quantity_sold)}
                                    {!!pi.stock && (
                                      <span style={{ opacity: 0.7 }}> of {Number(pi.stock)}</span>
                                    )}
                                  </>
                                ) : (
                                  getUnitsDescription({
                                    units: pi.stock,
                                    quantitySold: pi.quantity_sold,
                                    status: pi.catalog_status,
                                  })
                                )}
                              </span>
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </td>
                </ShowWhen>

                <td>
                  {item.short_url && (
                    <span class="CopyLink">
                      <span>{item.short_url}</span>
                      <CustomClipboard value={item.short_url} onCopy={trackCopyClick}>
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
                <ShowWhen additionalCondition={() => isBatchPaymentPages}>
                  <td>
                    <NavLink to={`/paymentpages/batchuploads/${item.id}/${item.title}`}>
                      <button>Batch Details</button>
                    </NavLink>
                  </td>
                </ShowWhen>
              </EntityItemRow>
            );
          })}
        </TableBody>
      </table>
    </div>
  );
};
