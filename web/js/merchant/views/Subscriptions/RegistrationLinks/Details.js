import React from 'react';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';
import RTracking from 'react-tracking';
import PropTypes from 'prop-types';

import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Tooltip from 'common/ui/Tooltip';
import Button from 'common/new-ui/Button';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import { InvoiceStatusLabel } from 'merchant/components/StatusLabel';

import MandatePaymentMethod from 'merchant/views/Subscriptions/components/MandatePaymentMethod';
import MandateCustomerDetails from 'merchant/views/Subscriptions/components/MandateCustomerDetails';
import NACHDetails from 'merchant/views/Subscriptions/components/UploadNACHForm/Details';
import SendLinkModal from 'merchant/components/SendLinkModal';

import {
  notifyCustomer,
  fetchRegistrationLink,
  downloadSignedNACHFile,
  cancelRegistrationLink,
} from 'merchant/reducers/registration_link';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import CopyLink from 'merchant/components/CopyLink';

import {
  trackOpenAuthLink,
  trackClickUploadNACHForm,
  trackClickDownloadNACHForm,
  trackClickViewNACHForm,
} from './gaAuth';
import analytics from '../analytics';

@withRouter
@connect(
  (state) => ({
    ...state.registrationLink,
  }),
  {
    fetchRegistrationLink,
    cancelRegistrationLink,
    showNotification,
    notifyCustomer,
    openModal,
    closeModal,
  },
)
@RTracking(() => window.rzpQ.component('RegistrationLinkDetailsContainer'))
export default class RegistrationLinkDetailsContainer extends React.Component {
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

  trackRegistrationLinkDetails = (event, options) => {
    if (!event) return;

    analytics.track(`registration_link.${event}`, options);
  };

  downloadSignedNACHFile = () => {
    this.trackRegistrationLinkDetails('nach.download_signed_nach.initiate');

    return downloadSignedNACHFile({
      auth_link_id: this.props.id,
    })
      .then(() => {
        this.trackRegistrationLinkDetails('nach.download_signed_nach.success');
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.trackRegistrationLinkDetails('nach.download_signed_nach.error', {
          response: err.errors[1],
        });
      });
  };

  onResendLinkSubmit = (notifyProps) => {
    const promises = [];

    if (notifyProps.email) {
      promises.push(this.props.notifyCustomer(this.props.id, 'email'));
    }
    if (notifyProps.sms) {
      promises.push(this.props.notifyCustomer(this.props.id, 'sms'));
    }

    return Promise.all(promises)
      .then((resp) => {
        this.props.showNotification({
          type: 'success',
          message: 'Link sent successfully!',
        });

        this.props.closeModal();

        this.trackRegistrationLinkDetails('resend.success');
        return resp;
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors,
        });

        this.trackRegistrationLinkDetails('resend.fail');

        return error;
      });
  };

  openResendLinkModal = () => {
    const { customer_details } = this.props.entity;

    this.trackRegistrationLinkDetails('resend.initiate');

    this.props.openModal({
      size: 'medium',
      component: (
        <SendLinkModal
          class="alert-sm"
          email={customer_details.customer_email}
          sms={customer_details.customer_contact}
          description="Are you sure you want to send the registration link again?"
          closeModal={this.props.closeModal}
          testModeMessage={
            <div>
              This registration link is created in <strong>Test Mode</strong>. So only test payments
              can be made for this registration link.
            </div>
          }
          onSubmit={this.onResendLinkSubmit}
        />
      ),
    });
  };

  cancelRegistrationLink = () => {
    this.trackRegistrationLinkDetails('cancel.initiate');

    this.context.confirm({
      header: 'Cancel Link?',
      message: () => (
        <div class="text-semi-muted">
          <p>The Link will be cancelled and the customer will not be able to pay for it.</p>
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

            this.trackRegistrationLinkDetails('cancel.success');
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors,
            });

            this.trackRegistrationLinkDetails('cancel.fail', {
              response: errors,
            });
          });
      },
      abort: () => {
        this.trackRegistrationLinkDetails('cancel.abort');
      },
    });
  };

  render() {
    const { loading: isLoading, entity, error } = this.props;
    const { subscription_registration = {} } = entity;

    const isSmsOrEmailSent = entity.sms_status === 'sent' || entity.email_status === 'sent';
    const isIssued = entity.status === 'issued';
    const isCancelled = entity.status === 'cancelled';
    const isSubscriptionRegistrationCreated = subscription_registration.status === 'created';

    const isResendAndCancelledAllowed = isSubscriptionRegistrationCreated && isIssued;

    return (
      <div class="content-wrapper content-sm txn-details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel RegistrationLinks--Details">
            <div class="panel-heading">
              {entity.id}
              {isResendAndCancelledAllowed && (
                <div class="btn-toolbar pull-right">
                  <button onClick={this.openResendLinkModal} class="btn Button--primary">
                    <Tooltip theme="dark">{isSmsOrEmailSent ? 'Resend Link' : 'Send Link'}</Tooltip>

                    <i className="i i-send" />
                  </button>
                </div>
              )}
            </div>
            <Alert type="error" message={error} />
            {!!Object.keys(entity).length && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* status of Registration Link */}
                    <EntityDetailRow label="Status">
                      <InvoiceStatusLabel status={entity.status} />

                      {isResendAndCancelledAllowed && (
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
                      <Amount value={entity.amount} currency={entity.currency} />
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
                    <EntityDetailRow label="Description" value={entity.description} />

                    {/* method */}
                    <EntityDetailRow label="Method">
                      <MandatePaymentMethod mandate={entity.subscription_registration} />
                    </EntityDetailRow>

                    {/* Customer Details */}
                    <EntityDetailRow label="Customer Details">
                      <MandateCustomerDetails customer={entity.customer_details} />
                    </EntityDetailRow>

                    {this.isNACHMethod && !isCancelled && (
                      <EntityDetailRow label="NACH form">
                        <NACHDetails
                          registrationLinkId={entity.id}
                          downloadSignedNACHFile={
                            entity.is_nach_form_uploaded && this.downloadSignedNACHFile
                          }
                          preFilledNachFileURL={
                            entity.token &&
                            entity.token.nach &&
                            entity.token.nach.prefilled_form_transient
                          }
                          trackClickUploadNACHForm={trackClickUploadNACHForm}
                          trackClickDownloadNACHForm={() => {
                            this.trackRegistrationLinkDetails('nach.download_pre_signed_form');

                            trackClickDownloadNACHForm();
                          }}
                          trackClickViewNACHForm={trackClickViewNACHForm}
                        />
                      </EntityDetailRow>
                    )}

                    {/* created at */}
                    <EntityDetailRow label="Created At">
                      <Time value={entity.created_at} format="LL, hh:mm A" />
                    </EntityDetailRow>

                    <EntityDetailRow label="Expiry">
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
