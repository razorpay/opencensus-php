import { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { titleCase } from 'rzp/utils/rzp-utils';
import { fetchInvoice } from 'merchant/modules/invoices/details';

import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import Definition from 'rzp/ui/Definition';
import Time from 'rzp/ui/Time';

import PaymentMethod from 'merchant/components/Subscriptions/MandatePaymentMethod';
import CustomerDetails from 'merchant/components/Subscriptions/MandateCustomerDetails';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

import CopyLink from 'merchant/components/Invoices/CopyLink';

@withRouter
@connect(
  state => ({
    ...state.invoice,
  }),
  { fetchInvoice }
)
export default class AuthLinkEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchInvoice(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchInvoice(nextProps.id);
    }
  }

  render() {
    const { loading: isLoading, invoice, error } = this.props;
    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">{invoice.id}</div>
            <Alert type="error" message={error} />
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  {/* status of auth link */}
                  <EntityDetailRow label="Status">
                    <InvoiceStatusLabel status={invoice.status} />
                  </EntityDetailRow>

                  {/* amount of invoice */}
                  <EntityDetailRow label="Amount">
                    <Amount
                      value={invoice.amount}
                      currency={invoice.currency}
                    />
                  </EntityDetailRow>

                  {/* currency for invoice */}
                  <EntityDetailRow label="Currency" value={invoice.currency} />

                  {/* link to invoice */}
                  <EntityDetailRow label="Link">
                    <CopyLink url={invoice.short_url} />
                  </EntityDetailRow>

                  {/* Receipt */}
                  <EntityDetailRow label="Receipt" value={invoice.receipt} />

                  {/* Description */}
                  <EntityDetailRow
                    label="Description"
                    value={invoice.description}
                  />

                  {/* method */}
                  <EntityDetailRow label="Method">
                    {invoice.mandate.method && (
                      <PaymentMethod mandate={invoice.mandate} />
                    )}
                  </EntityDetailRow>

                  {/* Customer Details */}
                  <EntityDetailRow label="Customer Details">
                    <CustomerDetails customer={invoice.customer_details} />
                  </EntityDetailRow>

                  {/* created at */}
                  <EntityDetailRow label="Created At">
                    <Time value={invoice.created_at} format="LL, hh:mm A" />
                  </EntityDetailRow>

                  <EntityDetailRow label="Expires By">
                    <Time value={invoice.expire_by} />
                  </EntityDetailRow>

                  <NestedEntityDetailRow label="Notes" value={invoice.notes} />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
