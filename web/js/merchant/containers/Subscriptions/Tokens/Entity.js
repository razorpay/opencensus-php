import { Component } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { connect } from 'react-redux';

import Spinner from 'rzp/ui/Spinner';
import Alert from 'rzp/ui/Forms/Alert';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Time from 'rzp/ui/Time';
import Definition from 'rzp/ui/Definition';

import EntityDetailRow from 'merchant/components/EntityDetailRow';
import NestedEntityDetailRow from 'merchant/components/NestedEntityDetailRow';
import PaymentMethod from 'merchant/components/Subscriptions/MandatePaymentMethod';
import CustomerDetails from 'merchant/components/Subscriptions/MandateCustomerDetails';
import { TokenStatusLabel } from 'merchant/components/StatusLabel';

import { fetchToken, deleteToken } from 'merchant/modules/token';
import { showNotification } from 'rzp/modules/notifications';
import { openModal, closeModal } from 'rzp/modules/modals';

import { getTokenStatus } from './List';
import ChargeToken from './ChargeToken';

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
      size: 'small',
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
            <div class="panel-heading">
              {entity.id}
              <div class="btn-toolbar pull-right">
                <button
                  class="btn btn-primary btn-sm"
                  onClick={this.handleChargeNow}
                >
                  Charge Now
                </button>
              </div>
            </div>
            <Alert type="error" message={error} />
            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  {/* status of token */}
                  <EntityDetailRow label="Status">
                    <TokenStatusLabel status={getTokenStatus(entity)} />
                  </EntityDetailRow>

                  <EntityDetailRow label="Failure Reason">
                    {entity.recurring_details &&
                    entity.recurring_details.failure_reason
                      ? entity.recurring_details.failure_reason
                      : '--'}
                  </EntityDetailRow>

                  {/*  */}
                  <EntityDetailRow label="Payment Method">
                    <PaymentMethod mandate={entity} />
                  </EntityDetailRow>

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
