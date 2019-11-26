import { Component } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import { AsyncBtn } from 'common/new-ui/Button';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Time from 'common/ui/Time';
import Definition from 'common/ui/Definition';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NACHDetails from 'merchant/components/Subscriptions/UploadNACHForm/Details';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import PaymentMethod from 'merchant/components/Subscriptions/MandatePaymentMethod';
import CustomerDetails from 'merchant/components/Subscriptions/MandateCustomerDetails';
import BankAccountDetails from 'merchant/components/Subscriptions/MandateBankAccountDetails';
import ShowWhen from 'merchant/components/ShowWhen';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';

import {
  fetchToken,
  deleteToken,
  resubmitNACHFile,
} from 'merchant/reducers/token';
import { downloadSignedNACHFile } from 'merchant/reducers/registration_link';
import { showNotification } from 'merchant_common/reducers/notifications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import { getTokenStatus } from './List';
import ChargeToken from './ChargeToken';

import {
  trackClickDownloadNACHForm,
  trackClickResubmitNachForm,
  trackClickViewNACHForm,
} from './ga';

@withRouter
@connect(state => ({ ...state.token }), {
  fetchToken,
  openModal,
  closeModal,
  deleteToken,
  showNotification,
})
export default class TokenEntityContainer extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  get isEmandateMethod() {
    return this.props.entity.method === 'emandate';
  }

  get isNACHMethod() {
    return this.props.entity.method === 'nach';
  }

  componentWillMount() {
    this.props.fetchToken(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchToken(nextProps.id);
    }
  }

  handleChargeNow = () => {
    this.props.openModal({
      size: 'medium',
      component: (
        <ChargeToken
          closeModal={this.props.closeModal}
          token={this.props.entity}
        />
      ),
    });
  };

  handleDeleteToken = () => {
    this.context.confirm({
      header: 'Delete Token?',
      message:
        'Once the token is deleted you will not be able to charge this token',
      affirmativeLabel: 'Yes, delete',
      abortLabel: "No, don't",
      affirmativePendingLabel: 'Deleting...',
      action: () => {
        return this.props
          .deleteToken(this.props.id)
          .then(resp => {
            if (resp) {
              this.props.showNotification({
                type: 'success',
                message: `The ${this.props.id} has been successfully delete`,
              });
              this.props.history.push('/tokens');
            } else {
              this.props.showNotification({
                type: 'error',
                message:
                  'An error occurred while deleting token. Kindly try again',
              });
            }
          })
          .catch(({ errors }) => {
            this.props.showNotification({
              type: 'error',
              message: errors[0],
            });
          });
      },
    });
  };

  downloadSignedNACHFile = () => {
    return downloadSignedNACHFile({
      token_id: this.props.id,
    }).catch(err => {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
    });
  };

  trackClickDownloadNACHForm = () => {
    const { failure_reason } = this.props.entity.recurring_details;

    trackClickDownloadNACHForm(
      failure_reason.includes('nach') ? 'Rejected' : 'Approved'
    );
  };

  render() {
    const { loading: isLoading, entity = {}, error } = this.props;

    const showChangeBtn =
      ['rejected', 'initiated'].indexOf(
        (entity.recurring_details || {}).status
      ) === -1;

    return (
      <div class="content-wrapper content-sm txn-details Token--Details">
        {isLoading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              {entity.id}
              <div class="btn-toolbar pull-right">
                {showChangeBtn && (
                  <button
                    class="btn btn-primary btn-sm"
                    onClick={this.handleChargeNow}
                  >
                    Charge Now
                  </button>
                )}
              </div>
            </div>
            <Alert type="error" message={error} />
            {!error && (
              <div class="SliderPanel__Body">
                <div class="panel-body">
                  <div class="list-group details-row-container">
                    {/* status of token */}
                    <EntityDetailRow label="Status">
                      <TokenStatusLabel status={getTokenStatus(entity)} />
                    </EntityDetailRow>

                    <EntityDetailRow label="Failure Reason">
                      <ErrorMessage
                        id={entity.id}
                        recurringDetails={entity.recurring_details}
                      />
                    </EntityDetailRow>

                    <EntityDetailRow label="Payment Method">
                      <PaymentMethod mandate={entity} />
                    </EntityDetailRow>

                    {this.isEmandateMethod && (
                      <ShowWhen featureEnabled="token_bank_details">
                        <EntityDetailRow label="Bank Account Details">
                          <BankAccountDetails
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
                          trackClickDownloadNACHForm={
                            this.trackClickDownloadNACHForm
                          }
                          trackClickViewNACHForm={trackClickViewNACHForm}
                        />
                      </EntityDetailRow>
                    )}

                    <EntityDetailRow label="Customer Details">
                      <CustomerDetails customer={entity.customer} />
                    </EntityDetailRow>

                    <EntityDetailRow label="Created At">
                      <TimeStamps token={entity} />
                    </EntityDetailRow>

                    <NestedEntityDetailRow label="Notes" value={entity.notes} />

                    <div class="pair-group-item">
                      <button
                        class="btn btn-default"
                        onClick={this.handleDeleteToken}
                      >
                        Delete Token
                      </button>
                    </div>
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
      <span class="text-primary">
        <Time value={token.created_at} format={timeFormat} />
      </span>
      <Definition>
        <></>
        <>
          Last Used At: <Time value={token.used_at} format={timeFormat} />
        </>
      </Definition>
    </ContentToggler>
  );
}

@connect(null, {
  showNotification,
})
class ErrorMessage extends React.PureComponent {
  static defaultProps = {
    recurringDetails: {
      failure_reason: '',
    },
  };

  resubmitNACHFile = () => {
    return resubmitNACHFile(this.props.id)
      .then(() => {
        trackClickResubmitNachForm();
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  render() {
    const {
        recurringDetails: { failure_reason },
      } = this.props,
      isNACHError = failure_reason && failure_reason.includes('nach');

    if (isNACHError) {
      return (
        <React.Fragment>
          <Alert type="error" message={failure_reason} showDismiss={false} />

          <AsyncBtn.Primary
            onClick={this.resubmitNACHFile}
            pendingState="Resubmitting..."
            class="btn"
          >
            Resubmit
          </AsyncBtn.Primary>
        </React.Fragment>
      );
    }

    return failure_reason || '--';
  }
}
