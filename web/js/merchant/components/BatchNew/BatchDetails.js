import React, { Component } from 'react';
import { NavLink } from 'react-router-dom';

import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Spinner from 'rzp/ui/Spinner';
import TableBody from 'rzp/ui/TableBody';
import { titleCase } from 'rzp/utils/rzp-utils';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { trackSeeAllLinks } from 'merchant/containers/BatchNew/ga';

import {
  BatchUploadStatusLabel,
  InvoiceStatusLabel,
} from 'merchant/components/StatusLabel';

const MAX_INVOICE_COUNT = 4;

export default function BatchDetails(props) {
  let { batch, stats, invoices, isLoading, onDownload } = props;
  let shouldShowAllInvoices = true;

  if (invoices && invoices.length >= MAX_INVOICE_COUNT) {
    invoices = invoices.slice(0, MAX_INVOICE_COUNT);
    shouldShowAllInvoices = false;
  }

  return (
    <div class="content-wrapper content-sm txn-details batch-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-plan text-primary" /> <strong>{batch.name}</strong>
          </div>
          <div class="SliderPanel__Body">
            <Banner
              cta="Download Report File"
              ctaOnClick={onDownload.bind(this, batch.id)}
            >
              <span>
                Download the output file containing all the payment links data.
              </span>
            </Banner>
            <div class="panel-body">
              <div class="stats-info equal-margin">
                <table class="table">
                  <tbody>
                    <tr>
                      <td class="td-info">
                        <span class="td-heading">Total rows processed</span>
                        <span class="td-value">{stats.batch_total}</span>
                      </td>
                      <td class="td-info">
                        <span class="td-heading">Payment Links Created</span>
                        <span class="td-value">{stats.issued_count}</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="td-info">
                        <span class="td-heading">Paid</span>
                        <span class="td-value text-success">
                          {stats.paid_count}
                        </span>
                      </td>
                      <td class="td-info">
                        <span class="td-heading">Expired</span>
                        <span class="td-value text-danger">
                          {stats.expired_count}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <div class="equal-margin">
                <EntityDetailRow
                  label="Status"
                  value={() => <BatchUploadStatusLabel status={batch.status} />}
                />
                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={batch.created_at} />}
                />
              </div>
              <hr />
              <div class="m-all p-t" style={{ overflow: 'auto' }}>
                <span class="pull-left">
                  <strong>{titleCase(batch.type)}</strong> created from this
                  batch.
                </span>
                {invoices.length > 0 && (
                  <NavLink
                    to={`/paymentlinks?batch_id=${batch.id}`}
                    className="btn-link pull-right"
                    onClick={() => trackSeeAllLinks(batch.id)}
                  >
                    View All {stats.batch_total} &gt;
                  </NavLink>
                )}
              </div>
              <div class="invoice-list table-responsive p-t">
                <table
                  class={`table table-hover${invoices.length > 0 &&
                    ' table-striped'}`}
                >
                  <TableBody
                    isLoading={isLoading}
                    rows={invoices}
                    colSpan={5}
                    emptyTableMsg="No invoices found"
                  >
                    {invoices.map(invoice => (
                      <InvoicesListItem key={invoice.id} invoice={invoice} />
                    ))}
                  </TableBody>
                </table>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

const InvoicesListItem = ({ invoice }) => {
  return (
    <EntityItemRow id={invoice.id}>
      <td>{invoice.customer_details.email}</td>
      <td>
        <Amount value={invoice.amount} currency={invoice.currency} />
      </td>
      <td>
        <InvoiceStatusLabel status={invoice.status} />
      </td>
    </EntityItemRow>
  );
};
