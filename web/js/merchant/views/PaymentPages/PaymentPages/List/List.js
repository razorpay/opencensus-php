import React from 'react';
import { NavLink } from 'react-router-dom';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import CustomClipboard from 'common/ui/Clipboard/Custom';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { getUnitsDescription } from 'merchant/views/PaymentPages/PaymentPages/utils';
import { BATCH_PAYMENT_PAGES_BASE_URL } from 'merchant/views/PaymentPages/PaymentPages/constants';
import ShowWhen from 'merchant/components/ShowWhen';
import Button from 'common/new-ui/Button';
import { analyticsTrack } from 'common/utils/analytics';
import {
  decodeHTMLEntities,
  getCommonAnalyticsProperties,
  getTableTemplateColumnsValue,
} from 'common/utils/rzp-utils';

import { trackListActions } from 'merchant/views/PaymentPages/PaymentPages/ga';
import track from './track';

import {
  Table,
  TableHeader,
  TableHeaderRow,
  TableHeaderCell,
  TableBody,
  TableRow,
  TableCell,
} from '@razorpay/blade/components';

export default ({ paymentPages, loading, isStorefrontPage, isBatchPaymentPages }) => {
  const trackCopyClick = (item) => {
    if (isStorefrontPage) {
      // storefront events capture
      analyticsTrack({
        objectName: 'Storefront URL',
        actionName: 'Copied',
        screen: 'Payment Page List Item',
        properties: {
          storefrontId: item.id,
          ...getCommonAnalyticsProperties(window.rzp_user, { isStorefrontPage: true }),
          product_page: 'Storefront Page',
        },
      });
    } else {
      trackListActions('Click Copy URL'); // sends to GA
      track.copyUrl(); // sends to lumberjack && Segment
    }
  };

  const trackTitleClick = () => {
    trackListActions('Title Click');
  };

  const ShowItem = ({ children }) => (
    <ShowWhen additionalCondition={() => isBatchPaymentPages}>{children}</ShowWhen>
  );

  const HideItem = ({ children }) => (
    <ShowWhen additionalCondition={() => !isBatchPaymentPages}>{children}</ShowWhen>
  );

  return (
    <Table
      data={{ nodes: paymentPages }}
      isLoading={loading}
      gridTemplateColumns={getTableTemplateColumnsValue(
        '2fr 1fr',
        !isBatchPaymentPages && '1fr',
        !isBatchPaymentPages && '1fr',
        '5fr 1fr 1fr',
        isBatchPaymentPages && '1fr',
      )}
    >
      {(tableData) => (
        <>
          <TableHeader>
            <TableHeaderRow>
              <TableHeaderCell>Title</TableHeaderCell>
              <TableHeaderCell>Total Sales</TableHeaderCell>
              <HideItem>
                <TableHeaderCell>Item Name</TableHeaderCell>
                <TableHeaderCell>Units Sold</TableHeaderCell>
              </HideItem>
              <TableHeaderCell>Page Url</TableHeaderCell>
              <TableHeaderCell>Created On</TableHeaderCell>
              <TableHeaderCell>Status</TableHeaderCell>
              <ShowItem>
                <TableHeaderCell>Actions</TableHeaderCell>
              </ShowItem>
            </TableHeaderRow>
          </TableHeader>
          <TableBody>
            {tableData.map((item) => {
              const { id, title } = item;
              item.payment_page_items = item?.payment_page_items || [];
              return (
                <TableRow key={id} item={item} testID={`entity-item-row-${id}`}>
                  <TableCell>
                    <HideItem>
                      <NavLink
                        to={`/paymentpages/${isStorefrontPage ? 'storefront/' : ''}${id}/payments${
                          isStorefrontPage ? '#storefront' : '#paymentpages'
                        }`}
                        onClick={trackTitleClick}
                      >
                        {title}
                      </NavLink>
                    </HideItem>
                    <ShowItem>
                      <NavLink
                        to={`${BATCH_PAYMENT_PAGES_BASE_URL}/${id}/payments#batchpaymentpages`}
                        onClick={trackTitleClick}
                      >
                        {title}
                      </NavLink>
                    </ShowItem>
                  </TableCell>
                  <TableCell>
                    <Amount value={item.total_amount_paid} currency={item.currency} />
                  </TableCell>
                  <HideItem>
                    <TableCell>
                      <table>
                        <tbody>
                          {item?.payment_page_items?.slice(0, 2).map((pi, ix) => {
                            const itemName = decodeHTMLEntities(pi?.item?.name);
                            return (
                              <tr key={ix}>
                                <td>
                                  <span class="item-ellipsis">{itemName}</span>
                                </td>
                              </tr>
                            );
                          })}
                          {item?.payment_page_items?.length > 2 && (
                            <tr>
                              <td>
                                <span class="help-content">
                                  <span class="more-btn">
                                    <b>+ {item.payment_page_items.length - 2} more</b>
                                  </span>
                                </span>
                              </td>
                            </tr>
                          )}
                        </tbody>
                      </table>
                    </TableCell>
                    <TableCell>
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
                    </TableCell>
                  </HideItem>
                  <TableCell>
                    {item.short_url && (
                      <span class="CopyLink">
                        <span>{item.short_url}</span>
                        <CustomClipboard value={item.short_url} onCopy={() => trackCopyClick(item)}>
                          <button class="btn btn-default btn-xs">copy</button>
                        </CustomClipboard>
                      </span>
                    )}
                  </TableCell>
                  <TableCell>
                    <Time value={item.created_at} />
                  </TableCell>
                  <TableCell>
                    <PaymentPagesStatusLabel status={item.status} />
                  </TableCell>
                  <ShowItem>
                    <TableCell>
                      <NavLink to={`/paymentpages/batchuploads/${item.id}/${item.title}`}>
                        <Button className="Button--primary">Batch Details</Button>
                      </NavLink>
                    </TableCell>
                  </ShowItem>
                </TableRow>
              );
            })}
          </TableBody>
        </>
      )}
    </Table>
  );
};
