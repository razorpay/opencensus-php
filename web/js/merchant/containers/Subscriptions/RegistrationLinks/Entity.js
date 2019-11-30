import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Button from 'common/new-ui/Button';

import PaymentMethod from 'merchant/components/Subscriptions/MandatePaymentMethod';
import CustomerDetails from 'merchant/components/Subscriptions/MandateCustomerDetails';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';
import NACHDetails from 'merchant/components/Subscriptions/UploadNACHForm/Details';

import {
  fetchRegistrationLink,
  downloadSignedNACHFile,
  cancelRegistrationLink,
} from 'merchant/reducers/registration_link';
import { showNotification } from 'merchant_common/reducers/notifications';

import CopyLink from 'merchant/components/CopyLink';

import {
  trackOpenAuthLink,
  trackClickUploadNACHForm,
  trackClickDownloadNACHForm,
  trackClickViewNACHForm,
} from './gaAuth';

@withRouter
@connect(
  state => ({
    ...state.registrationLink,
  }),
  { fetchRegistrationLink, showNotification, cancelRegistrationLink }
)
export default class RegistrationLinkEntityContainer extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  get paymentMethod() {
    return (
      this.props.entity.subscription_registration &&
      this.props.entity.subscription_registration.method
    );
  }

  get isNACHMethod() {
    return this.paymentMethod === 'nach';
  }

  componentWillMount() {
    this.props.fetchRegistrationLink(this.props.id);
  }

  componentDidMount() {
    trackOpenAuthLink();
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchRegistrationLink(nextProps.id);
    }
  }

  downloadSignedNACHFile = () => {
    return downloadSignedNACHFile({
      auth_link_id: this.props.id,
    }).catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  };

  cancelRegistrationLink = () => {
    this.context.confirm({
      header: 'Cancel Link?',
      message: () => (
        <div class="text-semi-muted">
          <p>
            The Link will be cancelled and the customer will not be able to pay
            for it.
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes, Cancel',
      affirmativePendingLabel: 'Cancelling...',
      abortLabel: "No, don't!",
      action: () => {
        return this.props
          .cancelRegistrationLink(this.props.entity)
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: 'Link cancelled!',
            });
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });
          });
      },
    });
  };

  render() {
    const { loading: isLoading, entity, error } = this.props;

    const isIssued = entity.status === 'issued',
      isTotalAmountPaid = entity.amount === entity.amount_paid,
      showCancelLink = isIssued && !isTotalAmountPaid;

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel RegistrationLinks--Details">
            <div class="panel-heading">{entity.id}</div>
            <Alert type="error" message={error} />
            {!!Object.keys(entity).length && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* status of Registration Link */}
                    <EntityDetailRow label="Status">
                      <InvoiceStatusLabel status={entity.status} />

                      {showCancelLink && (
                        <Button.Transparent
                          class="Button--Link cancel-link"
                          onClick={this.cancelRegistrationLink}
                        >
                          Cancel Link
                        </Button.Transparent>
                      )}
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
                      <PaymentMethod
                        mandate={entity.subscription_registration}
                      />
                    </EntityDetailRow>

                    {/* Customer Details */}
                    <EntityDetailRow label="Customer Details">
                      <CustomerDetails customer={entity.customer_details} />
                    </EntityDetailRow>

                    {this.isNACHMethod && (
                      <EntityDetailRow label="NACH form">
                        <NACHDetails
                          registrationLinkId={entity.id}
                          downloadSignedNACHFile={
                            entity.is_nach_form_uploaded &&
                            this.downloadSignedNACHFile
                          }
                          preFilledNachFileURL={
                            entity.token &&
                            entity.token.nach &&
                            entity.token.nach.prefilled_form
                          }
                          trackClickUploadNACHForm={trackClickUploadNACHForm}
                          trackClickDownloadNACHForm={
                            trackClickDownloadNACHForm
                          }
                          trackClickViewNACHForm={trackClickViewNACHForm}
                        />
                      </EntityDetailRow>
                    )}

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
