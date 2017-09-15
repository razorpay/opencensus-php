import { Component } from 'react';
import { NavLink } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import { titleCase } from 'rzp/utils/rzp-utils';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen from 'merchant/components/ShowWhen';
import LineItemReadOnlyTable from 'merchant/components/Invoices/LineItemReadOnlyTable';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

// Note: class is needed for "ref" to work in parent component
export default class InvoiceDetail extends Component {
  render() {
    let {
      invoice,
      isValidInvoice,
      isLoading,
      statusMsg,
      curInvoiceIndex,
      subscriptionStatus,
      nextChargeAt,
    } = this.props;

    // For next_due invoice, issued_at is charge_at of subscription*
    return (
      <div class="content-wrapper content-sm txn-details">
        {isValidInvoice && isLoading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : !isValidInvoice
            ? <div class="no-data-message">
                There is no upcoming invoice for this subscription.
              </div>
            : Object.keys(invoice).length
              ? <div class="panel panel-default SliderPanel">
                  <div class="panel-heading">
                    {this.props.onClose &&
                      <button
                        type="button"
                        class="close close-secondary"
                        onClick={this.props.onClose}
                      >
                        <i class="icon icon-arrow-back" />
                        <i class="icon icon-close" />
                      </button>}
                    <i class="icon icon-link text-primary icon--formal" />{' '}
                    <span class="txn-details-title">
                      <div class="txn-details-title--primary">
                        <Time value={invoice.issued_at} format="MMM DD, YYYY" />
                      </div>
                      {curInvoiceIndex &&
                        <div class="txn-details-title--secondary">
                          Recurring Payment #{curInvoiceIndex}
                        </div>}
                    </span>
                  </div>

                  <div class="SliderPanel__Body">
                    <div class="panel-body">
                      <div class="list-group details-row-container">
                        <EntityDetailRow
                          label="Invoice Id"
                          value={
                            invoice.status === 'next_due'
                              ? 'Not yet created'
                              : () =>
                                  <NavLink
                                    to={`/invoices/${invoice.id}`}
                                    target="_blank"
                                  >
                                    {invoice.id}
                                    <i class="icon icon-external-link" />
                                  </NavLink>
                          }
                        />
                        <EntityDetailRow
                          label="Invoice Status"
                          value={() =>
                            <InvoiceStatusLabel status={invoice.status} />}
                        />
                        <EntityDetailRow
                          label="Created at"
                          value={() =>
                            <Time
                              value={invoice.date}
                              format="DD MMM YYYY, hh:mm:ss a"
                            />}
                        />

                        {/* dummy invoice with 'next_due' status won't be added if subscriptionStatus is pending, so label will be 'Charge at'*/}
                        {
                          do {
                            if (
                              invoice.status === 'next_due' ||
                              (invoice.status === 'issued' &&
                                subscriptionStatus !== 'halted')
                            ) {
                              <EntityDetailRow
                                label={`${subscriptionStatus === 'pending'
                                  ? 'Next Charge at'
                                  : 'Charge at'}`}
                                value={() =>
                                  <Time
                                    value={nextChargeAt}
                                    format="DD MMM YYYY, hh:mm:ss a"
                                  />}
                              />;
                            }
                          }
                        }

                        <EntityDetailRow
                          label="Amount"
                          value={() =>
                            <Amount
                              currency={invoice.currency}
                              value={invoice.amount}
                            />}
                        />
                      </div>
                    </div>
                  </div>
                </div>
              : <div class="empty-content" />}
      </div>
    );
  }
}
