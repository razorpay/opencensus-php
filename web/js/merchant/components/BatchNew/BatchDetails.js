import React, { Component } from 'react';
import { NavLink } from 'react-router-dom';

import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import Banner from 'rzp/ui/Banner';
import Spinner from 'rzp/ui/Spinner';
import TableBody from 'rzp/ui/TableBody';
import { titleCase, pluralize } from 'rzp/utils/rzp-utils';
import EntityItemRow from 'merchant/containers/EntityItemRow';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

import { trackSeeAllLinks } from 'merchant/containers/BatchNew/ga';

import {
  BatchUploadStatusLabel,
  InvoiceStatusLabel,
} from 'merchant/components/StatusLabel';

const MAX_INVOICE_COUNT = 4;

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

export default function BatchDetails({ renderDetails, ...props }) {
  console.log(props);
  let { batch = {}, stats = {}, invoices = [], isLoading, onDownload } = props;
  let batchName =
    batch.name && batch.name.length > 24
      ? `${batch.name.substr(0, 24)}...`
      : batch.name;

  if (invoices && invoices.length >= MAX_INVOICE_COUNT) {
    invoices = invoices.slice(0, MAX_INVOICE_COUNT);
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
            <i class="i i-plan text-primary" />{' '}
            <strong>{batchName || batch.id}</strong>
          </div>
          <div class="SliderPanel__Body">
            <Banner
              cta="Download Report"
              ctaOnClick={onDownload.bind(this, batch.id)}
            >
              <span>
                Download the report containing all Payment Links data.
              </span>
            </Banner>
            <div class="panel-body">
              {renderDetails && renderDetails(props)}
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

              {invoices.length > 0 ? (
                <div class="m-all p-t" style={{ overflow: 'auto' }}>
                  <span class="pull-left">
                    <strong>
                      {pluralize(titleCase(batch.type), stats.batch_total)}
                    </strong>{' '}
                    created from this batch.
                  </span>{' '}
                  <NavLink
                    to={`/paymentlinks?batch_id=${batch.id}`}
                    className="btn-link pull-right"
                    onClick={() => trackSeeAllLinks(batch.id)}
                  >
                    View All &gt;
                  </NavLink>
                </div>
              ) : null}

              <div class="invoice-list table-responsive p-t">
                <table
                  class={`table table-hover${invoices.length > 0 &&
                    ' table-striped'}`}
                >
                  <TableBody
                    isLoading={isLoading}
                    rows={invoices}
                    colSpan={5}
                    emptyTableMsg="No Payment Links found"
                  >
                    {invoices.map(invoice => (
                      <InvoicesListItem key={invoice.id} invoice={invoice} />
                    ))}
                  </TableBody>
                </table>
                {stats.batch_total > 0 &&
                  stats.batch_total > stats.issued_count &&
                  batch.status !== 'created' && (
                    <small class="help-block m-l">
                      <i class="i i-info-circle" />
                      {/* show error info */}
                      {stats.issued_count === 0 ? (
                        <span>
                          The payment links related to this batch were not
                          created due to errors. Please{' '}
                          <span
                            class="btn-link"
                            onClick={onDownload.bind(this, batch.id)}
                          >
                            download
                          </span>{' '}
                          the report containing all Payment Links data
                        </span>
                      ) : (
                        <span>
                          Some links related to this batch were not created due
                          to errors. Please{' '}
                          <span
                            class="btn-link"
                            onClick={onDownload.bind(this, batch.id)}
                          >
                            download
                          </span>{' '}
                          the report containing all Payment Links data.
                        </span>
                      )}
                    </small>
                  )}
              </div>
              {invoices.length > 0 ? (
                <small class="help-block m-l text-center">
                  Showing {invoices.length} of {stats.batch_total}
                </small>
              ) : null}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
