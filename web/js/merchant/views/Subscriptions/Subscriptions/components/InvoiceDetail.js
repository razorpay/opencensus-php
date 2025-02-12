import rTracking from 'react-tracking';
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
import { isDomesticCardOrIsUPI } from 'merchant/views/Subscriptions/utils';
import { compose } from 'redux';

// Note: class is needed for "ref" to work in parent component

class SubscriptionsInvoiceDetail extends Component {
  componentDidMount() {
    this.props.tracking.trackEvent(
      window.rzpQ.subscription().interaction('subscription.invoice.details'),
    );
  }

  getAddOnList() {
    if (
      isDomesticCardOrIsUPI(
        this.props.subscription.payment_method,
        this.props.subscription.card_mandate_id,
      )
    ) {
      return null;
    }

    const invoiceStatus = this.props.invoice.status;
    const addons = this.props.addons;

    const addonsList = addons.map((addon, key) => {
      return (
        <div className={`m-b ${invoiceStatus === 'next_due' && 'addons'}`} key={`addon-${key}`}>
          {invoiceStatus === 'next_due' && (
            <div className="edit-layer">
              <span
                className="i i-close text-danger"
                onClick={() => this.props.onAddOnDelete(addon.id)}
              />
            </div>
          )}
          <div style={{ position: 'relative' }}>
            <div className="label--primary" style={{ marginBottom: '4px' }}>
              {addon.item.name}
            </div>
            <div className="label--primary">
              <Amount
                currency={addon.item.currency}
                value={addon.quantity * addon.item.unit_amount}
              />
            </div>
            <small className="label--secondary">
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
        <button
          className="btn-link no-padding"
          key="include-more"
          onClick={this.props.showAddOnModal}
        >
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

    const shouldShowAttemptCharge = isDomesticCardOrIsUPI(
      subscription.payment_method,
      subscription.card_mandate_id,
    );
    const showAttemptChargeCTA =
      ['active', 'pending', 'halted', 'completed'].indexOf(subscription.status) > -1 ||
      (subscription.status === 'cancelled' &&
        (curInvoiceIndex > 1 || (subscription.type !== 3 && subscription.type !== 1)));

    if (!isValidInvoice) {
      invoiceContent = (
        <div className="panel panel-default SliderPanel">
          <div>
            {this.props.onClose && (
              <button type="button" className="close close-secondary" onClick={this.props.onClose}>
                <i className="i i-arrow-back" />
                <i className="i i-close" />
              </button>
            )}
          </div>

          <div className="no-data-message">There is no upcoming invoice for this subscription.</div>
        </div>
      );
    } else if (Object.keys(invoice).length) {
      invoiceContent = (
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            {this.props.onClose && (
              <button type="button" className="close close-secondary" onClick={this.props.onClose}>
                <i className="i i-arrow-back" />
                <i className="i i-close" />
              </button>
            )}
            <i className="i i-link text-primary icon--formal" />{' '}
            <span className="txn-details-title">
              <div className="txn-details-title--primary">
                <Time value={invoice.billing_start} format="MMM DD, YYYY" />
              </div>
              {curInvoiceIndex && (
                <div className="txn-details-title--secondary">
                  Recurring Payment #{curInvoiceIndex}
                </div>
              )}
            </span>
          </div>

          <div className="SliderPanel__Body">
            <div className="panel-body">
              <div className="list-group details-row-container">
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
                            <i className="i i-external-link" />
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

                        {showAttemptChargeCTA && !shouldShowAttemptCharge && (
                          <AsyncButton
                            className="btn btn-default m-t"
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
                      <div className="label--primary">
                        <Amount
                          currency={invoice.currency}
                          value={subscription.quantity * plan.item.unit_amount}
                        />
                      </div>
                      {subscription.quantity && (
                        <small className="label--secondary">
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
      invoiceContent = <div className="empty-content" />;
    }

    // For next_due invoice, billing_start is charge_at of subscription*
    return (
      <div className="content-wrapper content-sm txn-details">
        {isValidInvoice && isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          invoiceContent
        )}
      </div>
    );
  }
}

export default compose(rTracking(() => window.rzpQ.component('InvoiceDetail')))(
  SubscriptionsInvoiceDetail,
);
