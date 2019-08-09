import { connect } from 'react-redux';

import { updatePPInReduxList } from 'merchant/modules/invoices/list';
import { keysToSentence } from 'common/util';

import {
  fetchPaymentPageEntity,
  fetchPaymentPageEntitySettings,
  fetchPaymentsListForPaymentPage,
  editPaymentPage,
  activatePaymentPage,
  deactivatePaymentPage,
} from '../model';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Spinner from 'rzp/ui/Spinner';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { trackDetailViewEdits, trackShareActions } from '../ga';

import NoEntityResultsFound from 'common/NoEntityResultsFound';

import PaymentPagesV2Entity from './V2';
import PaymentPagesV3Entity from './V3';

import ActivateAgain from '../Modals/ActivateAgain';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'Total payments made reached Times payable limit',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(state => ({ user: state.session.user }), {
  updatePPInReduxList,
  showNotification,
  closeModal,
  openModal,
})
export default class extends React.Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {
    paymentPageEntity: {},
    paymentPagePayments: [],
    paymentsListLoading: true,
    createdByUser: null,
  };

  componentWillMount() {
    this.fetchEntity(this.props.id);
    this.fetchEntityPayments(this.props.id);
  }

  componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.fetchEntity(nextProps.id);
      this.fetchEntityPayments(nextProps.id);
    }
  }

  fetchEntity(id) {
    this.setState({
      loading: true,

      createdByUser: null,
      paymentPageEntity: {},
      paymentPagePayments: [],
      paymentsListLoading: true,
    });

    return fetchPaymentPageEntity(id)
      .then(resp => {
        if (resp) {
          this.setState({
            paymentPageEntity: resp.data,
            createdByUser: resp.data.user,
          });
        }

        this.setState({ loading: false });

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ loading: false });
      });
  }

  fetchSettings(id) {}

  fetchEntityPayments(id) {
    return fetchPaymentsListForPaymentPage(id)
      .then(resp => {
        if (resp) {
          this.setState({ paymentPagePayments: resp.data.items });
        }

        this.setState({ paymentsListLoading: false });

        return resp;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        this.setState({ paymentsListLoading: false });
      });
  }

  editPaymentPage = data => {
    return editPaymentPage(this.state.paymentPageEntity.id, data)
      .then(resp => {
        if (resp.data) {
          this.props.updatePPInReduxList(resp.data, false);

          const keys = { ...data };
          if (keys.hasOwnProperty('times_payable')) {
            keys.quantity = keys.times_payable;
            delete keys.times_payable;
          }

          this.props.showNotification({
            type: 'success',
            message: `${keysToSentence(keys)} updated successfully`,
          });

          this.setState({
            paymentPageEntity: resp.data,
          });

          return resp;
        } else {
          throw 'Some network issue occured';
        }
      })
      .catch(({ errors }) => {
        let err = errors;

        if (Array.isArray(err)) {
          err = [];

          errors.length &&
            errors.forEach(e => {
              if (e && e.toLowerCase().indexOf('status code') === -1) {
                err.push(e);
              }
            });

          err = err.length ? err : null;
        }

        if (!err) {
          err = `Some network error has occured`;
        }

        this.props.showNotification({
          type: 'error',
          message: err,
        });
      });
  };

  /*
   * 1) Direct manual Activation can be done only when it was manually closed
   * 2) Direct manual deactivation can be done any time user wants while in Active State;
   **/
  toggleManualActivation = () => {
    const newStatus = 'active';

    const status = this.state.paymentPageEntity.status;
    const statusReason = this.state.paymentPageEntity.status_reason;

    const isActive = status === 'active';
    const isDeactivated =
      statusReason && statusReason.toLowerCase() === 'deactivated';

    let apiAction,
      header,
      message,
      affirmativeLabel,
      affirmativePendingLabel,
      successMsg;

    if (isActive) {
      /* Wants manual deactivation */
      apiAction = deactivatePaymentPage;
      header = 'Deactivate Page?';
      message =
        'Once you deactivate the page, you will not be able to accept payments till you activate it again.';
      affirmativeLabel = 'Yes, deactivate';
      affirmativePendingLabel = 'Deactivating..';
      successMsg = `${this.state.paymentPageEntity.id} is now Inactive`;
    } else if (isDeactivated) {
      /* Wants activation for manual deactivation for cancelled status */

      apiAction = activatePaymentPage;
      header = 'Activate Page?';
      message =
        'Once you activate the page, you will be able to accept payments.';
      affirmativeLabel = 'Yes, activate';
      affirmativePendingLabel = 'Activating..';
      successMsg = `${this.state.paymentPageEntity.id} is now Active`;
    }

    this.context.confirm({
      header,
      message: () => (
        <div class="text-semi-muted">
          <p>{message}</p>
        </div>
      ),
      affirmativeLabel,
      affirmativePendingLabel,
      abortLabel: "No, don't!",
      action: () => {
        return apiAction(this.state.paymentPageEntity.id)
          .then(resp => {
            if (resp.data) {
              this.props.showNotification({
                type: 'success',
                message: successMsg,
              });

              this.props.closeModal();

              this.props.updatePPInReduxList(resp.data, false);

              this.setState({
                paymentPageEntity: resp.data,
              });
              trackDetailViewEdits(
                'Toggle Status',
                isActive ? 'deactivate' : 'activate'
              );
            }
            return resp;
          })
          .catch(({ errors }) => {
            let err = errors;

            if (Array.isArray(err)) {
              err = [];

              errors.length &&
                errors.forEach(e => {
                  if (e && e.toLowerCase().indexOf('status code') === -1) {
                    err.push(e);
                  }
                });

              err = err.length ? err : null;
            }

            if (!err) {
              err = `Some network error has occured`;
            }

            this.props.showNotification({
              type: 'error',
              message: err,
            });
          });
      },
    });
  };

  reActivateLink = () => {
    let statusReason = this.state.paymentPageEntity.status_reason;

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';
    const isDeactivated = statusReason.toLowerCase() === 'deactivated';

    if (isDeactivated) {
      this.toggleManualActivation();
      return;
    }

    const currentTimeStamp = moment().unix();
    // Ideally, it should consider 2 min window, because it would take time for merchant to update.
    const reactivationTimeGap = 15 * 60;
    const hasExpiredInCompletedState =
      this.state.paymentPageEntity.expire_by &&
      this.state.paymentPageEntity.expire_by <
        currentTimeStamp + reactivationTimeGap; // within 15 minutes

    this.props.openModal({
      size: 'medium',
      component: (
        <ActivateAgain
          reactivationTimeGap={reactivationTimeGap}
          expireBy={
            isExpired || hasExpiredInCompletedState
              ? this.state.paymentPageEntity.expire_by
              : undefined
          }
          timesPayable={
            isCompleted ? this.state.paymentPageEntity.times_payable : undefined
          }
          timesPaid={
            isCompleted ? this.state.paymentPageEntity.times_paid : undefined
          }
          handleClose={this.props.closeModal}
          handleClick={data => {
            return activatePaymentPage(this.state.paymentPageEntity.id, data)
              .then(resp => {
                if (resp.data) {
                  this.props.updatePPInReduxList(resp.data, false);

                  this.setState({
                    paymentPageEntity: resp.data,
                  });

                  this.props.showNotification({
                    type: 'success',
                    message: `${this.state.paymentPageEntity.id} is now Active`,
                  });
                } else {
                  throw 'Some network error has occured';
                }

                return resp;
              })
              .catch(({ errors }) => {
                let err = errors;

                if (Array.isArray(err)) {
                  err = [];

                  errors.length &&
                    errors.forEach(e => {
                      if (e && e.toLowerCase().indexOf('status code') === -1) {
                        err.push(e);
                      }
                    });

                  err = err.length ? err : null;
                }

                if (!err) {
                  err = `Some network error has occured`;
                }

                this.props.showNotification({
                  type: 'error',
                  message: err,
                });

                throw errors;
              });
          }}
        />
      ),
    });
  };

  render() {
    let { paymentPageEntity, loading } = this.state;

    if (loading) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage">
          <div class="page-spinner-container">
            <Spinner />
          </div>
        </div>
      );
    }

    if (!loading && !Object.keys(paymentPageEntity).length) {
      return (
        <div class="content-wrapper content-sm txn-details Entity--paymentpage">
          <NoEntityResultsFound
            error={
              <span>
                No results found for id: <i>{this.props.id}</i>
              </span>
            }
          />
        </div>
      );
    }

    return this.props.user.isPPV3Enabled ? (
      <PaymentPagesV3Entity
        {...this.props}
        {...this.state}
        fetchEntity={this.fetchEntity}
        fetchEntityPayments={this.fetchEntityPayments}
        editPaymentPage={this.editPaymentPage}
        toggleManualActivation={this.toggleManualActivation}
        reActivateLink={this.reActivateLink}
      />
    ) : (
      <PaymentPagesV2Entity
        {...this.props}
        {...this.state}
        fetchEntity={this.fetchEntity}
        fetchEntityPayments={this.fetchEntityPayments}
        editPaymentPage={this.editPaymentPage}
        toggleManualActivation={this.toggleManualActivation}
        reActivateLink={this.reActivateLink}
      />
    );
  }
}
