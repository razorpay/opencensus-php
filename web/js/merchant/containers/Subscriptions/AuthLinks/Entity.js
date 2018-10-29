import { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';

import PaymentMethod from 'merchant/components/Subscriptions/MandatePaymentMethod';
import CustomerDetails from 'merchant/components/Subscriptions/MandateCustomerDetails';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

import { fetchAuthLink } from 'merchant/modules/auth_link';

import CopyLink from 'merchant/components/Invoices/CopyLink';

@withRouter
@connect(
  state => ({
    ...state.authLink,
  }),
  { fetchAuthLink }
)
export default class AuthLinkEntityContainer extends Component {
  componentWillMount() {
    this.props.fetchAuthLink(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchAuthLink(nextProps.id);
    }
  }

  render() {
    const { loading: isLoading, entity, error } = this.props;
    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">{entity.id}</div>
            <Alert type="error" message={error} />
            {!!Object.keys(entity).length && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* status of auth link */}
                    <EntityDetailRow label="Status">
                      <InvoiceStatusLabel status={entity.status} />
                    </EntityDetailRow>

                    {/* amount of entity */}
                    <EntityDetailRow label="Amount">
                      <Amount
                        value={entity.amount}
                        currency={entity.currency}
                      />
                    </EntityDetailRow>

                    {/* currency for entity */}
                    <EntityDetailRow label="Currency" value={entity.currency} />

                    {/* link to entity */}
                    <EntityDetailRow label="Link">
                      <CopyLink url={entity.short_url} />
                    </EntityDetailRow>

                    {/* Receipt */}
                    <EntityDetailRow label="Receipt" value={entity.receipt} />

                    {/* Description */}
                    <EntityDetailRow
                      label="Description"
                      value={entity.description}
                    />

                    {/* method */}
                    <EntityDetailRow label="Method">
                      {entity.subscription_registration.method && (
                        <PaymentMethod
                          mandate={entity.subscription_registration}
                        />
                      )}
                    </EntityDetailRow>

                    {/* Customer Details */}
                    <EntityDetailRow label="Customer Details">
                      <CustomerDetails customer={entity.customer_details} />
                    </EntityDetailRow>

                    {/* created at */}
                    <EntityDetailRow label="Created At">
                      <Time value={entity.created_at} format="LL, hh:mm A" />
                    </EntityDetailRow>

                    <EntityDetailRow label="Expires By">
                      <Time value={entity.expire_by} />
                    </EntityDetailRow>

                    <NestedEntityDetailRow label="Notes" value={entity.notes} />
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </div>
    );
  }
}
