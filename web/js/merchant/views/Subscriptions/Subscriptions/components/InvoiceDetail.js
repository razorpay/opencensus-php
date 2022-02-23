import RTracking from 'react-tracking';
import { Component } from 'react';
import { NavLink } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import { paymentId, status, createdAt } from 'common/ui/item/pair';
import DataTable from 'common/ui/Table/DataTable';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import AsyncButton from 'react-async-button';

// Note: class is needed for "ref" to work in parent component
@RTracking(() => window.rzpQ.component('InvoiceDetail'))
export default class SubscriptionsInvoiceDetail extends Component {
  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.subscription().interaction('subscription.invoice.details'),
    );
  }

  getAddOnList() {
    const isUPIPaymentMethod = this.props.subscription.payment_method === 'upi';

    if (isUPIPaymentMethod) return null;

    const invoiceStatus = this.props.invoice.status;
    const addons = this.props.addons;

    const addonsList = addons.map((addon, key) => {
      return (
        <div class={`m-b ${invoiceStatus === 'next_due' && 'addons'}`} key={`addon-${key}`}>
          {invoiceStatus === 'next_due' && (
            <div class="edit-layer">
              <span
                class="i i-close text-danger"
                onClick={() => this.props.onAddOnDelete(addon.id)}
              />
            </div>
          )}
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
              <Amount currency={addon.item.currency} value={addon.item.unit_amount} />
              {'  '}
              per unit
            </small>
          </div>
        </div>
      );
    });

    if (invoiceStatus === 'next_due') {
      addonsList.push(
        <button class="btn-link no-padding" key="include-more" onClick={this.props.showAddOnModal}>
          + Include {addonsList.length > 0 ? 'another' : ''} Add-on
        </button>,
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
    const {
      mode,
      invoice,
      isValidInvoice,
      isLoading,
      curInvoiceIndex,
      subscription,
      plan,
      nextChargeAt,
      onManualAttempt,
      isSubscriptionOffersEnabled,
    } = this.props;

    let invoiceContent;
    const showAttemptChargeCTA =
      ['active', 'pending', 'halted', 'completed'].indexOf(subscription.status) > -1 ||
      (subscription.status === 'cancelled' &&
        (curInvoiceIndex > 1 || (subscription.type !== 3 && subscription.type !== 1)));

    if (!isValidInvoice) {
      invoiceContent = (
        <div class="panel panel-default SliderPanel">
          <div>
            {this.props.onClose && (
              <button type="button" class="close close-secondary" onClick={this.props.onClose}>
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
          </div>

          <div class="no-data-message">There is no upcoming invoice for this subscription.</div>
        </div>
      );
    } else if (Object.keys(invoice).length) {
      invoiceContent = (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {this.props.onClose && (
              <button type="button" class="close close-secondary" onClick={this.props.onClose}>
                <i class="i i-arrow-back" />
                <i class="i i-close" />
              </button>
            )}
            <i class="i i-link text-primary icon--formal" />{' '}
            <span class="txn-details-title">
              <div class="txn-details-title--primary">
                <Time value={invoice.billing_start} format="MMM DD, YYYY" />
              </div>
              {curInvoiceIndex && (
                <div class="txn-details-title--secondary">Recurring Payment #{curInvoiceIndex}</div>
              )}
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
                      : () => (
                          <NavLink
                            to={`/invoices/${invoice.id}`}
                            target="_blank"
                            rel="noreferrer noopener"
                            onClick={() => {
                              this.props.tracking.trackEvent(
                                window.rzpQ.subscription().interaction('subscription.invoice.id'),
                              );
                            }}
                          >
                            {invoice.id}
                            <i class="i i-external-link" />
                          </NavLink>
                        )
                  }
                />
                <EntityDetailRow
                  label="Invoice Status"
                  value={() => <InvoiceStatusLabel status={invoice.status} />}
                />

                <EntityDetailRow
                  label={`${mode === 'test' ? 'Bill Date' : 'Created at'}`}
                  value={() => (
                    <Time value={invoice.billing_start} format="DD MMM YYYY, hh:mm:ss a" />
                  )}
                />

                {invoice.status === 'next_due' && subscription.status !== 'pending' && (
                  <EntityDetailRow
                    label="Charge at"
                    value={() => (
                      <div>
                        <div>
                          <Time
                            value={
                              subscription.status === 'halted'
                                ? invoice.billing_start
                                : nextChargeAt
                            }
                            format="DD MMM YYYY, hh:mm:ss a"
                          />
                        </div>
                      </div>
                    )}
                  />
                )}

                {invoice.status === 'issued' && (
                  <EntityDetailRow
                    label={`${subscription.status === 'pending' ? 'Next Charge at' : 'Charge at'}`}
                    value={() => (
                      <div>
                        <div>
                          <Time value={nextChargeAt} format="DD MMM YYYY, hh:mm:ss a" />
                        </div>

                        {showAttemptChargeCTA && (
                          <AsyncButton
                            class="btn btn-default m-t"
                            text=" Attempt Charge"
                            pendingText="Attempting..."
                            onClick={() => onManualAttempt(invoice.id, subscription.id)}
                          />
                        )}
                      </div>
                    )}
                  />
                )}

                <EntityDetailRow
                  label="Recurring Amount"
                  value={() => (
                    <div>
                      <div class="label--primary">
                        <Amount
                          currency={invoice.currency}
                          value={subscription.quantity * plan.item.unit_amount}
                        />
                      </div>
                      {subscription.quantity && (
                        <small class="label--secondary">
                          {subscription.quantity} x{' '}
                          <Amount currency={invoice.currency} value={plan.item.unit_amount} /> per
                          unit
                        </small>
                      )}
                    </div>
                  )}
                />

                {this.getAddOnList()}

                {isSubscriptionOffersEnabled && (
                  <EntityDetailRow
                    label="Offer Discount"
                    value={() =>
                      invoice.offer_amount ? (
                        <Amount currency={invoice.currency} value={invoice.offer_amount} />
                      ) : (
                        '--'
                      )
                    }
                  />
                )}

                <EntityDetailRow
                  label="Total Amount"
                  value={() => <Amount currency={invoice.currency} value={invoice.amount} />}
                />

                {invoice.payments && (
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
                      subTitle: `${invoice.payments.count} ${
                        invoice.payments.count > 1 ? 'attempts' : 'attempt'
                      }`,
                    }}
                  />
                )}
              </div>
            </div>
          </div>
        </div>
      );
    } else {
      invoiceContent = <div class="empty-content" />;
    }

    // For next_due invoice, billing_start is charge_at of subscription*
    return (
      <div class="content-wrapper content-sm txn-details">
        {isValidInvoice && isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          invoiceContent
        )}
      </div>
    );
  }
}
