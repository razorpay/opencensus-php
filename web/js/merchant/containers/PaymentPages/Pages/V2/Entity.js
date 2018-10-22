import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import { updatePPInReduxList } from 'merchant/modules/invoices/list';
import { keysToSentence } from 'common/util';

import {
  fetchPaymentPageEntity,
  fetchPaymentsListForPaymentPage,
  editPaymentPage,
  activatePaymentPage,
  deactivatePaymentPage,
  sendLink,
} from '../model';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Definition from 'rzp/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import StatsInfo from 'ui/StatsTable';
import { getKeysSeparatedByPipe } from 'rzp/utils/rzp-utils';

import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { trackDetailViewEdits, trackShareActions } from '../ga';

import EditStocks from '../Edit/EditStocks';

import NoEntityResultsFound from 'common/NoEntityResultsFound';

import {
  EditExpiry,
  EditNotes,
  EditReceipt,
} from '../../../PaymentLinks/Edit/index';
import ActivateAgain from '../Modals/ActivateAgain';
import ShareView from '../Modals/Share';

import Button from 'component/Button';

const MAX_API_COUNT = 100;

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'Total payments made reached Times payable limit',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(state => ({ user: state.session.user }), {
  updatePPInReduxList,
  showNotification,
  openModal,
  closeModal,
})
export default class PaymentPagesV2Entity extends React.Component {
  state = {
    paymentPageEntity: {},
    paymentPagePayments: [],
    paymentsListLoading: true,
    user: null,
  };

  static contextTypes = {
    confirm: PropTypes.func,
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

      paymentPageEntity: {},
      paymentPagePayments: [],
      paymentsListLoading: true,
      user: null,
    });

    return fetchPaymentPageEntity(id)
      .then(resp => {
        if (resp) {
          this.setState({
            paymentPageEntity: resp.data,
            user: resp.data.user,
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

  getStatsTable(paymentPageEntity) {
    return [
      [
        {
          title: 'Number of Payments made',
          value: paymentPageEntity.times_paid,
        },
        {
          title: 'Total revenue in sales',
          value: (
            <Amount
              value={paymentPageEntity.total_amount_paid}
              currency={paymentPageEntity.currency}
            />
          ),
        },
      ],
    ];
  }

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

  openShareView = () => {
    trackDetailViewEdits('Click Share');
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          handleClick={this.sendLink}
          handleAction={sendLink.bind(null, this.state.paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          url={this.state.paymentPageEntity.short_url}
          title={this.state.paymentPageEntity.title}
          description={this.state.paymentPageEntity.description}
          trackerFn={trackShareActions}
        />
      ),
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

  editPaymentPage = data => {
    return editPaymentPage(this.state.paymentPageEntity.id, data)
      .then(resp => {
        if (resp.data) {
          this.props.updatePPInReduxList(resp.data, false);

          this.props.showNotification({
            type: 'success',
            message: `${keysToSentence(data)} updated successfully`,
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

  render() {
    let {
      paymentPageEntity,
      loading,
      paymentPagePayments,
      paymentsListLoading,
    } = this.state;

    const isRoleAllowedEdit = this.props.user.isAllowedEdit('payment_pages');

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

    let status = paymentPageEntity.status;
    let statusReason = paymentPageEntity.status_reason;

    const isActive = status === 'active';
    const isExpired = !isActive && statusReason.toLowerCase() === 'expired';

    const isCompleted = !isActive && statusReason.toLowerCase() === 'completed';

    const isSmsOrEmailSent =
      paymentPageEntity.sms_status === 'sent' ||
      paymentPageEntity.email_status === 'sent';

    return (
      <div class="content-wrapper content-sm txn-details Entity--paymentpage Entity--paymentpage-v2">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-payment-pages text-primary icon--formal" />{' '}
            <div class="text">{paymentPageEntity.title}</div>
            <div class="btn-toolbar pull-right">
              {isRoleAllowedEdit && (
                <Link
                  class="btn Button Button--primary--invert btn-sm"
                  to={`/paymentpages/${paymentPageEntity.id}/edit`}
                  target="_blank"
                >
                  Edit
                </Link>
              )}
              {isRoleAllowedEdit &&
                isActive && (
                  <button
                    class="btn btn-primary btn-sm"
                    onClick={this.openShareView}
                  >
                    Share
                  </button>
                )}
            </div>
          </div>

          <div class="SliderPanel__Body">
            <div class="panel-body">
              <div class="list-group details-row-container">
                <StatsInfo stats={this.getStatsTable(paymentPageEntity)} />
                <div class="stats-info-footer">
                  <Link
                    target="_blank"
                    to={`/payments?payment_link_id=${
                      paymentPageEntity.id
                    }&count=${MAX_API_COUNT}&ref=paymentpages`}
                  >
                    View payments for this page <i class="i i-chevron-right" />
                  </Link>
                </div>

                <EntityDetailRow
                  label="Payment Page title"
                  value={paymentPageEntity.title}
                />

                <EntityDetailRow
                  label="Amount"
                  value={() => (
                    <Amount
                      value={paymentPageEntity.amount}
                      currency={paymentPageEntity.currency}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Available Stock"
                  value={() => (
                    <EditStocks
                      value={paymentPageEntity.times_payable}
                      timesPaid={paymentPageEntity.times_paid}
                      editFn={this.editPaymentPage}
                      entityId={paymentPageEntity.id}
                      trackerFn={trackDetailViewEdits}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Page URL"
                  value={() => (
                    <CopyLink
                      url={paymentPageEntity.short_url}
                      onCopy={() => {
                        trackDetailViewEdits('Click Copy');
                      }}
                    />
                  )}
                />
                <EntityDetailRow
                  label="Page Status"
                  value={() => (
                    <div>
                      <PaymentPagesStatusLabel status={status} />
                      {isRoleAllowedEdit && (
                        <Button.Transparent
                          class="Button--Link"
                          style={{ marginLeft: 12 }}
                          onClick={
                            isActive
                              ? this.toggleManualActivation
                              : this.reActivateLink
                          }
                        >
                          {isActive ? 'Deactivate' : 'Activate'}
                        </Button.Transparent>
                      )}
                      <div style={{ marginTop: 4, color: '#8991ae' }}>
                        {inActiveStatusReasonMap[statusReason]}
                      </div>
                    </div>
                  )}
                />

                <EntityDetailRow
                  label="Payment Page Id"
                  value={paymentPageEntity.id}
                />

                <EntityDetailRow label="Created by">
                  {!!this.state.user ? (
                    <Definition>
                      {this.state.user.name}
                      {this.state.user.email}
                    </Definition>
                  ) : (
                    'API'
                  )}
                </EntityDetailRow>

                <EntityDetailRow
                  label="Created At"
                  value={() => <Time value={paymentPageEntity.created_at} />}
                />

                <EntityDetailRow
                  label={isExpired ? 'Expired On' : 'Expires On'}
                  value={() => (
                    <EditExpiry
                      value={paymentPageEntity.expire_by}
                      editFn={this.editPaymentPage}
                      entityId={paymentPageEntity.id}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Receipt No."
                  value={() => (
                    <EditReceipt
                      value={paymentPageEntity.receipt}
                      entityId={paymentPageEntity.id}
                      editFn={this.editPaymentPage}
                      trackerFn={trackDetailViewEdits}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />

                <EntityDetailRow
                  label="Notes"
                  value={() => (
                    <EditNotes
                      value={paymentPageEntity.notes}
                      editFn={this.editPaymentPage}
                      entityId={paymentPageEntity.id}
                      trackerFn={trackDetailViewEdits}
                      isRoleAllowedEdit={isRoleAllowedEdit}
                    />
                  )}
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
