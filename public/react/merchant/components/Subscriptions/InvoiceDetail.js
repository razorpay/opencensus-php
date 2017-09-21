import { Component } from 'react';
import { NavLink } from 'react-router-dom';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';
import Spinner from 'rzp/ui/Spinner';
import { titleCase } from 'rzp/utils/rzp-utils';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import { paymentId, status, createdAt } from 'rzp/ui/item/pair';
import DataTable from 'rzp/ui/Table/DataTable';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AsyncButton from 'react-async-button';

const notificationClassMap = {
  sent: 'text-success',
  pending: 'text-warning',
};

// Note: class is needed for "ref" to work in parent component
export default class InvoiceDetail extends Component {
  getAddOnList() {
    let invoiceStatus = this.props.invoice.status;
    let addons = this.props.addons;

    let addonsList;

    addonsList = addons.map((addon, key) => {
      return (
        <div
          class={`m-b ${invoiceStatus === 'next_due' && 'addons'}`}
          key={`addon-${key}`}
        >
          {invoiceStatus === 'next_due' &&
            <div class="edit-layer">
              <span
                class="icon icon-close text-danger"
                onClick={() => this.props.onAddOnDelete(addon.id)}
              />
            </div>}
          <div style={{ position: 'relative' }}>
            <div class="label--primary" style={{ marginBottom: '4px' }}>
              {addon.item.name}
            </div>
            <div class="label--primary">
              <Amount
                currency={addon.item.currency}
                value={addon.quantity * addon.item.unit_amount}
              />
            </div>
            <small class="label--secondary">
              {addon.quantity} x{'  '}
              <Amount
                currency={addon.item.currency}
                value={addon.item.unit_amount}
              />
              {'  '}
              per unit
            </small>
          </div>
        </div>
      );
    });

    if (invoiceStatus === 'next_due') {
      addonsList.push(
        <button
          class="btn-link no-padding"
          key="include-more"
          onClick={this.props.showAddOnModal}
        >
          + Include {addonsList.length > 0 ? 'another' : ''} Add-on
        </button>
      );
    }

    return (
      <EntityDetailRow
        label="Add-Ons"
        value={() => {
          return addonsList.length ? addonsList : '--';
        }}
      />
    );
  }

  render() {
    let {
      invoice,
      isValidInvoice,
      isLoading,
      statusMsg,
      curInvoiceIndex,
      subscription,
      plan,
      nextChargeAt,
      onManualAttempt,
    } = this.props;

    let invoiceContent;

    if (!isValidInvoice) {
      invoiceContent = (
        <div class="panel panel-default SliderPanel">
          <div>
            {this.props.onClose &&
              <button
                type="button"
                class="close close-secondary"
                onClick={this.props.onClose}
              >
                <i class="icon icon-arrow-back" />
                <i class="icon icon-close" />
              </button>}
          </div>

          <div class="no-data-message">
            There is no upcoming invoice for this subscription.
          </div>
        </div>
      );
    } else if (Object.keys(invoice).length) {
      invoiceContent = (
        <div class="panel panel-default SliderPanel">
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
                <EntityDetailRow label="Invoice Status">
                  <div>
                    <InvoiceStatusLabel status={invoice.status} />
                    {invoice.status === 'issued' &&
                      [
                        'active',
                        'pending',
                        'halted',
                        'completed',
                        'cancelled',
                      ].indexOf(subscription.status) > -1 &&
                      <AsyncButton
                        class="btn-link no-padding"
                        text=" Attempt Charge?"
                        pendingText="Attempting..."
                        onClick={() => onManualAttempt(invoice.id)}
                      />}
                  </div>
                </EntityDetailRow>
                <EntityDetailRow
                  label="Created at"
                  value={() =>
                    <Time
                      value={invoice.date}
                      format="DD MMM YYYY, hh:mm:ss a"
                    />}
                />

                {/* dummy invoice with 'next_due' status won't be added if subscription status is pending, so label will be 'Charge at'*/}
                {
                  do {
                    if (
                      invoice.status === 'next_due' ||
                      (invoice.status === 'issued' &&
                        subscription.status !== 'halted')
                    ) {
                      <EntityDetailRow
                        label={`${subscription.status === 'pending'
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
                  label="Recurring Amount"
                  value={() =>
                    <div>
                      <div class="label--primary">
                        <Amount
                          currency={plan.item.currency}
                          value={subscription.quantity * plan.item.unit_amount}
                        />
                      </div>
                      <small class="label--secondary">
                        {subscription.quantity} x{' '}
                        <Amount
                          currency={plan.item.currency}
                          value={plan.item.unit_amount}
                        />{' '}
                        per unit
                      </small>
                    </div>}
                />

                {this.getAddOnList()}

                <EntityDetailRow
                  label="Total Amount"
                  value={() =>
                    <Amount
                      currency={invoice.currency}
                      value={invoice.amount}
                    />}
                />

                {invoice.payments &&
                  <DataTable
                    customClass="payments-table"
                    progressLoader={true}
                    title="Payments"
                    columns={[paymentId, status, createdAt]}
                    items={invoice.payments.items}
                    loading={isLoading}
                    showHeaders={false}
                    noStripe={true}
                    panelHeading={{
                      title: 'Payments',
                      subTitle: `${invoice.payments.count} ${invoice.payments
                        .count > 1
                        ? 'attempts'
                        : 'attempt'}`,
                    }}
                  />}
              </div>
            </div>
          </div>
        </div>
      );
    } else {
      invoiceContent = <div class="empty-content" />;
    }

    // For next_due invoice, issued_at is charge_at of subscription*
    return (
      <div class="content-wrapper content-sm txn-details">
        {isValidInvoice && isLoading
          ? <div class="page-spinner-container">
              <Spinner />
            </div>
          : invoiceContent}
      </div>
    );
  }
}
