import React, { Component } from 'react';

import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Spinner from 'rzp/ui/Spinner';
import TableBody from 'rzp/ui/TableBody';
import { titleCase } from 'rzp/utils/rzp-utils';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import {
  BatchUploadStatusLabel,
  InvoiceStatusLabel,
} from 'merchant/components/StatusLabel';

export default function BatchDetails(props) {
  let { batch, stats, invoices, isLoading } = props;
  let shouldShowAllInvoices = true;
  const MAX_INVOICE_COUNT = 4;

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
            <i class="i i-link text-primary icon--formal" />{' '}
            <strong>{batch.name}</strong>
          </div>
          <div class="SliderPanel__Body">
            {/* TODO: remove below link */}
            <Banner cta="Download Report File" ctaUrl="#">
              <span>
                Download the output file containing all the payment links data.
              </span>
            </Banner>
            <div class="panel-body">
              <div class="stats-info">
                <table class="table">
                  <tbody>
                    <tr>
                      <td class="td-info">
                        <span class="td-heading">Payment Links Created</span>
                        <span class="td-value">{stats.entities_processed}</span>
                      </td>
                      <td class="td-info">
                        <span class="td-heading">Payment Links Sent</span>
                        <span class="td-value">{stats.payment_links_sent}</span>
                      </td>
                    </tr>
                    <tr>
                      <td class="td-info">
                        <span class="td-heading">Paid</span>
                        <span class="td-value text-success">{stats.paid}</span>
                      </td>
                      <td class="td-info">
                        <span class="td-heading">Expired</span>
                        <span class="td-value text-danger">
                          {stats.expired}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <EntityDetailRow
                label="Status"
                value={() => <BatchUploadStatusLabel status={batch.status} />}
              />
              <EntityDetailRow
                label="Created At"
                value={() => <Time value={batch.created_at} />}
              />
              <hr />
              <div class="m-all" style={{ overflow: 'auto' }}>
                <span class="pull-left">
                  <strong>{titleCase(batch.type)}</strong> created from this
                  batch.
                </span>
                {!shouldShowAllInvoices && (
                  <a class="btn-link pull-right" href="#">
                    View all <strong>{invoices.length}</strong> &gt;
                  </a>
                )}
              </div>
              <div class="table-responsive p-all">
                <table class="table table-hover">
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
              {/* TODO: add link below */}
              {!shouldShowAllInvoices && (
                <a class="btn btn-default btn-block" href="#">
                  View All {invoices.length}
                </a>
              )}
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
