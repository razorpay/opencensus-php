import React, { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import rTracking from 'react-tracking';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import Button, { AsyncBtn } from 'common/new-ui/Button';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import Alert from 'common/ui/Forms/Alert';
import Popover, { PopoverBody } from 'common/ui/Popover';
import Spinner from 'common/ui/Spinner';
import Time from 'common/ui/Time';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { rupeesToPaise, getFormattedAmountNew, currencySymbols } from 'common/utils/rzp-utils';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import ShowWhen from 'merchant/components/ShowWhen';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';
import { downloadSignedNACHFile } from 'merchant/reducers/registration_link';
import { fetchToken, deleteToken, resubmitNACHFile, cancelToken } from 'merchant/reducers/token';
import analytics from 'merchant/views/Subscriptions/analytics';
import MandateBankAccountDetails from 'merchant/views/Subscriptions/components/MandateBankAccountDetails';
import MandateCustomerDetails from 'merchant/views/Subscriptions/components/MandateCustomerDetails';
import MandatePaymentMethod from 'merchant/views/Subscriptions/components/MandatePaymentMethod';
import NACHDetails from 'merchant/views/Subscriptions/components/UploadNACHForm/Details';
import { CARD_AFA_MAX_LIMIT } from 'merchant/views/Subscriptions/constants';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';

import { getTokenStatus } from './List';
import ChargeToken from './components/ChargeToken';
import {
  trackClickDownloadNACHForm,
  trackClickResubmitNachForm,
  trackClickViewNACHForm,
} from './ga';

class ErrorMessageComponent extends React.PureComponent {
  static defaultProps = {
    recurringDetails: {
      failure_reason: '',
    },
  };

  resubmitNACHFile = () => {
    this.props.trackTokenDetailsView('nach.download_signed_nach.initiate');

    return resubmitNACHFile(this.props.id)
      .then(() => {
        trackClickResubmitNachForm();

        this.props.showNotification({
          type: 'success',
          message: 'NACH file re-submitted successfully',
        });

        this.props.trackTokenDetailsView('nach.download_signed_nach.success');
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });

        this.props.trackTokenDetailsView('nach.download_signed_nach.fail', {
          response: errors,
        });
      });
  };

  render() {
    const {
      recurringDetails: { failure_reason },
      isNACHMethod,
    } = this.props;

    if (isNACHMethod && failure_reason) {
      return (
        <EntityDetailRow label="Failure Reason">
          <Alert type="error" message={failure_reason} showDismiss={false} />

          <AsyncBtn.Primary
            onClick={this.resubmitNACHFile}
            pendingState="Resubmitting..."
            className="btn"
          >
            Resubmit
          </AsyncBtn.Primary>
        </EntityDetailRow>
      );
    }
    return null;
  }
}

const ErrorMessage = compose(
  connect(null, {
    showNotification,
  }),
)(ErrorMessageComponent);

class TokenDetailsContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  get isEmandateMethod() {
    return this.props.entity.method === 'emandate';
  }

  get isNACHMethod() {
    return this.props.entity.method === 'nach';
  }

  get isCardMethod() {
    return this.props.entity.method === 'card';
  }

  get isUPIMethod() {
    return this.props.entity.method === 'upi';
  }

  UNSAFE_componentWillMount() {
    this.props.fetchToken(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchToken(nextProps.id);
    }
  }

  trackTokenDetailsView = (event, options) => {
    if (!event) return;

    analytics.track(`token.${event}`, options);
  };

  handleChargeNow = () => {
    this.trackTokenDetailsView('update.charge_now');

    this.props.openModal({
      size: 'medium',
      component: <ChargeToken closeModal={this.props.closeModal} token={this.props.entity} />,
    });
  };

  handleDeleteToken = () => {
    this.trackTokenDetailsView('delete.initiate');

    this.context.confirm({
      header: 'Delete Token?',
      message: 'Once the token is deleted you will not be able to charge this token',
      affirmativeLabel: 'Yes, delete',
      abortLabel: "No, don't",
      affirmativePendingLabel: 'Deleting...',
      action: () => {
        return this.props
          .deleteToken(this.props.id)
          .then((resp) => {
            if (resp) {
              this.props.showNotification({
                type: 'success',
                message: `The ${this.props.id} has been successfully delete`,
              });
              this.props.history.push('/tokens');
            } else {
              this.props.showNotification({
                type: 'error',
                message: 'An error occurred while deleting token. Kindly try again',
              });
            }

            this.trackTokenDetailsView('delete.confirm');
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors[0],
            });

            this.trackTokenDetailsView('delete.fail', { response: errors[1] });
          });
      },
      abort: () => {
        this.trackTokenDetailsView('delete.abandon');
      },
    });
  };

  handleCancelToken = () => {
    this.trackTokenDetailsView('cancel.initiate');

    this.context.confirm({
      header: 'Cancel Token?',
      message: 'Once the token is cancel you will not be able to charge this token',
      affirmativeLabel: 'Yes, Cancel',
      abortLabel: "No, don't",
      affirmativePendingLabel: 'Cancelling...',
      action: () => {
        return this.props
          .cancelToken(this.props.entity.customer.id, this.props.id)
          .then((resp) => {
            if (resp) {
              this.props.showNotification({
                type: 'success',
                message: `The ${this.props.id} has been successfully cancelled`,
              });
            }

            this.trackTokenDetailsView('cancel.confirm');
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors[0],
            });

            this.trackTokenDetailsView('cancel.fail', { response: errors[1] });
          });
      },
      abort: () => {
        this.trackTokenDetailsView('cancel.abandon');
      },
    });
  };

  downloadSignedNACHFile = () => {
    this.trackTokenDetailsView('nach.download_signed_nach.initiate');

    return downloadSignedNACHFile({
      token_id: this.props.id,
    })
      .then(() => {
        this.trackTokenDetailsView('nach.download_signed_nach.success');
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.trackTokenDetailsView('nach.download_signed_nach.error', {
          response: err.errors[1],
        });
      });
  };

  trackClickDownloadNACHForm = () => {
    const { failure_reason } = this.props.entity.recurring_details;
    const status = failure_reason.includes('nach') ? 'Rejected' : 'Approved';
    trackClickDownloadNACHForm(status);

    this.trackTokenDetailsView('nach.download.error', { response: status });
  };

  render() {
    const { loading: isLoading, entity = {}, error, user } = this.props;

    const isCancelled = !isLoading && getTokenStatus(entity) === 'cancelled';
    const merchantCurrency = user.merchant.currency;
    const currencySym = currencySymbols[merchantCurrency];
    const showChangeBtn =
      !isCancelled &&
      ['rejected', 'initiated'].indexOf((entity.recurring_details || {}).status) === -1;

    let expireAt = entity.subscription_registration && entity.subscription_registration.expire_at;
    let maxAmount = null;
    let isDomesticCard = null;
    let defaultAFAMaxAmount = rupeesToPaise(CARD_AFA_MAX_LIMIT);
    if (entity.method === 'card') {
      expireAt = entity.expired_at;
      isDomesticCard = !entity.card.international;

      maxAmount =
        entity?.subscription_registration?.max_amount || rupeesToPaise(CARD_AFA_MAX_LIMIT);
      if (maxAmount <= defaultAFAMaxAmount) {
        defaultAFAMaxAmount = maxAmount;
      }
    }

    return (
      <div className="content-wrapper content-sm txn-details Token--Details">
        {isLoading ? (
          <div className="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div className="panel panel-default SliderPanel">
            <div className="panel-heading">{entity.id}</div>
            <Alert type="error" message={error} />
            {!error && (
              <div className="SliderPanel__Body">
                <div className="panel-body">
                  <div className="list-group details-row-container">
                    <div className="charge-now">
                      <MandateCustomerDetails customer={entity.customer} />

                      {showChangeBtn && (
                        <Button.Primary onClick={this.handleChargeNow}>
                          {currencySym} Charge Now
                        </Button.Primary>
                      )}
                    </div>

                    {/* status of token */}
                    <EntityDetailRow label="Status">
                      <TokenStatusLabel className="m-r" status={getTokenStatus(entity)} />
                    </EntityDetailRow>
                    <ErrorMessage
                      id={entity.id}
                      isNACHMethod={this.isNACHMethod}
                      recurringDetails={entity.recurring_details}
                      trackTokenDetailsView={this.trackTokenDetailsView}
                    />
                    <EntityDetailRow label="Payment Method">
                      <MandatePaymentMethod mandate={entity} user={user} />
                    </EntityDetailRow>

                    {(this.isEmandateMethod || this.isNACHMethod) && (
                      <ShowWhen featureEnabled="token_bank_details">
                        <EntityDetailRow label="Bank Account Details">
                          <MandateBankAccountDetails
                            bankDetails={entity.bank_details}
                            bank={entity.bank}
                          />
                        </EntityDetailRow>
                      </ShowWhen>
                    )}

                    {this.isNACHMethod && (
                      <EntityDetailRow label="NACH Form">
                        <NACHDetails
                          downloadSignedNACHFile={this.downloadSignedNACHFile}
                          trackClickDownloadNACHForm={this.trackClickDownloadNACHForm}
                          trackClickViewNACHForm={trackClickViewNACHForm}
                        />
                      </EntityDetailRow>
                    )}

                    <EntityDetailRow label="Created At">
                      <TimeStamps token={entity} />
                    </EntityDetailRow>

                    <EntityDetailRow label="Expiry">
                      {expireAt ? <Time value={expireAt} /> : 'Until Cancelled'}
                    </EntityDetailRow>
                    {this.isCardMethod && maxAmount && isDomesticCard && (
                      <EntityDetailRow label="Max Auto-debit Amount">
                        <Amount value={maxAmount} currency={merchantCurrency} />{' '}
                        <span>
                          <i className="i i-info-circle" />
                          <Popover theme="dark">
                            <PopoverBody>
                              {`You can automatically charge the customer upto
                                ${getFormattedAmountNew(
                                  maxAmount,
                                  true,
                                  merchantCurrency,
                                )} for each recurring payment. Payments above
                                ${getFormattedAmountNew(
                                  defaultAFAMaxAmount,
                                  true,
                                  merchantCurrency,
                                )} will ask for OTP verification from customer.`}
                            </PopoverBody>
                          </Popover>
                        </span>
                      </EntityDetailRow>
                    )}
                    <NestedEntityDetailRow label="Notes" value={entity.notes} />

                    <EntityDetailRow label="Actions" className="pair-group-item actions">
                      {this.isUPIMethod && !isCancelled && (
                        <button
                          className="btn btn-default"
                          onClick={this.handleCancelToken}
                          style={{
                            marginRight: 8,
                          }}
                        >
                          <i className="i i-close" /> Cancel Token
                        </button>
                      )}

                      <button
                        className="btn Button--invert Button--danger"
                        onClick={this.handleDeleteToken}
                      >
                        <i className="i i-delete" /> Delete Token
                      </button>
                    </EntityDetailRow>
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

function TimeStamps({ token }) {
  const timeFormat = 'LL, hh:mm A';
  return (
    <ContentToggler>
      <span className="text-primary">
        <Time value={token.created_at} format={timeFormat} />
      </span>
      <Definition>
        <>
          Last Used At: <Time value={token.used_at} format={timeFormat} />
        </>
      </Definition>
    </ContentToggler>
  );
}

export default compose(
  connect(
    (state) => ({
      ...state.token,
      user: state.session.user,
    }),
    {
      fetchToken,
      openModal,
      closeModal,
      cancelToken,
      deleteToken,
      showNotification,
    },
  ),
  rTracking(() => window.rzpQ.component('TokenDetailsContainer')),
  withRouter,
)(TokenDetailsContainer);
