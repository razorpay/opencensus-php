import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';

import { updatePPInReduxList } from 'merchant/modules/invoices/list';

import {
  fetchPaymentPageEntity,
  fetchPaymentsListForPaymentPage,
  editPaymentPage,
  activatePaymentPage,
  deactivatePaymentPage,
  sendLink,
} from './model';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CopyLink from 'merchant/components/Invoices/CopyLink';
import ShowWhen from 'merchant/components/ShowWhen';
import StatsInfo from 'ui/StatsTable';
import GroupDetailsTable from 'rzp/ui/GroupDetailsTable';

import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { trackDetailViewEdits } from './ga';

import EditPaymentFor from './Edit/EditPaymentFor';
import EditTimesPayable from './Edit/EditTimesPayable';

import {
  EditExpiry,
  EditNotes,
  EditReceipt,
} from '../../PaymentLinks/Edit/index';
import ActivateAgain from './Modals/ActivateAgain';
import ShareView from './Modals/Share';

import Button from 'component/Button';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'Total payments made reached Times payable limit',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(null, {
  updatePPInReduxList,
  showNotification,
  openModal,
  closeModal,
})
export default class PaymentPagesEntity extends React.Component {
  state = {
    paymentPage: {},
    paymentPagePayments: [],
    paymentsListLoading: true,
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
      this.fetchEntityPayments(this.props.id);
    }
  }

  fetchEntity(id) {
    this.setState({ loading: true });

    return fetchPaymentPageEntity(id)
      .then(resp => {
        if (resp) {
          this.setState({ paymentPage: resp.data });
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

  getStatsTable(paymentPage) {
    return [
      [
        { title: 'Payments Made', value: paymentPage.times_paid },
        {
          title: 'Total Sales',
          value: (
            <Amount
              value={paymentPage.total_amount_paid}
              currency={paymentPage.currency}
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

  onCopy = ({ paymentPageId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Payment Pages',
      eventAction: 'Copy - Payment Page Link',
      eventLabel: `payment_link_id=${paymentPageId}`,
    });
  };

  openShareView = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          handleClick={this.sendLink}
          handleAction={sendLink.bind(null, this.state.paymentPage.id)}
          showNotification={this.props.showNotification}
          url={this.state.paymentPage.short_url}
          title={this.state.paymentPage.title}
          description={this.state.paymentPage.description}
        />
      ),
    });
  };

  reActivateLink = () => {
    let statusReason = this.state.paymentPage.status_reason;

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';
    const isDeactivated = statusReason.toLowerCase() === 'deactivated';

    if (isDeactivated) {
      this.toggleManualActivation();
      return;
    }

    const currentTimeStamp = moment().unix();
    // Ideally, it should consider 2 min window, because it would take time for merchant to update.
    const hasExpiredInCompletedState =
      this.props.expire_by && this.props.expire_by < currentTimeStamp;

    this.props.openModal({
      size: 'medium',
      component: (
        <ActivateAgain
          expireBy={
            isExpired || hasExpiredInCompletedState
              ? this.state.paymentPage.expire_by
              : undefined
          }
          timesPayable={
            isCompleted ? this.state.paymentPage.times_payable : undefined
          }
          handleClose={this.props.closeModal}
          handleClick={data => {
            return activatePaymentPage(this.state.paymentPage.id, data).then(
              resp => {
                if (resp.data) {
                  updatePPInReduxList(resp.data, false);
                  this.setState({
                    paymentPage: resp.data,
                  });
                }

                return resp;
              }
            );
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

    const status = this.state.paymentPage.status;
    const statusReason = this.state.paymentPage.status_reason;

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
      header = 'Deactivate Link?';
      message =
        'Once you deactivate the link, you will not be able to accept payments till you activate it again.';
      affirmativeLabel = 'Yes, deactivate';
      affirmativePendingLabel = 'Deactivating..';
      successMsg = `${this.state.paymentPage.id} link is now Inactive`;
    } else if (isDeactivated) {
      /* Wants activation for manual deactivation for cancelled status */

      apiAction = activatePaymentPage;
      header = 'Activate Link?';
      message =
        'Once you activate the link, you will be able to accept payments.';
      affirmativeLabel = 'Yes, activate';
      affirmativePendingLabel = 'Activating..';
      successMsg = `${this.state.paymentPage.id} link is now Active`;
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
        return apiAction(this.state.paymentPage.id)
          .then(resp => {
            if (resp.data) {
              this.props.showNotification({
                type: 'success',
                message: successMsg,
              });

              this.props.closeModal();

              updatePPInReduxList(resp.data, false);

              this.setState({
                paymentPage: resp.data,
              });
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
    return editPaymentPage(this.state.paymentPage.id, data)
      .then(resp => {
        if (resp.data) {
          this.props.updatePPInReduxList(resp.data, false);

          this.props.showNotification({
            type: 'success',
            message: `${this.state.paymentPage.id} successfully Updated`,
          });

          this.setState({
            paymentPage: resp.data,
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
      paymentPage,
      loading,
      paymentPagePayments,
      paymentsListLoading,
    } = this.state;

    let status = paymentPage.status;
    let statusReason = paymentPage.status_reason;

    const isActive = !loading && status === 'active';
    const isExpired =
      !loading && !isActive && statusReason.toLowerCase() === 'expired';

    const isCompleted =
      !loading && !isActive && statusReason.toLowerCase() === 'completed';

    const isSmsOrEmailSent =
      paymentPage.sms_status === 'sent' || paymentPage.email_status === 'sent';

    return (
      <div class="content-wrapper content-sm txn-details Entity--paymentpage">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary icon--formal" />{' '}
              <strong>{paymentPage.id}</strong>
              <ShowWhen notMyRole="support finance">
                <div class="btn-toolbar pull-right">
                  {isActive && (
                    <button
                      class="btn btn-primary btn-sm"
                      onClick={this.openShareView}
                    >
                      Send Link
                    </button>
                  )}
                </div>
              </ShowWhen>
            </div>

            <div class="SliderPanel__Body">
              <div class="panel-body">
                <div class="list-group details-row-container">
                  <StatsInfo stats={this.getStatsTable(paymentPage)} />
                  <EntityDetailRow
                    label="Amount"
                    value={() => (
                      <Amount
                        value={paymentPage.amount}
                        currency={paymentPage.currency}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Link URL"
                    value={() => (
                      <CopyLink
                        url={paymentPage.short_url}
                        onCopy={() => {
                          this.onCopy({
                            paymentPageId: paymentPage.id,
                          });
                        }}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <div>
                        <PaymentPagesStatusLabel status={status} />
                        <Button.Transparent
                          class="Button--Link"
                          style={{ marginLeft: 12 }}
                          onClick={
                            isActive
                              ? this.toggleManualActivation
                              : this.reActivateLink
                          }
                        >
                          {isActive ? 'Deactivate Link' : 'Activate Link'}
                        </Button.Transparent>
                        <div class="text-danger" style={{ marginTop: 4 }}>
                          {inActiveStatusReasonMap[statusReason]}
                        </div>
                      </div>
                    )}
                  />
                  <EntityDetailRow
                    label="Payment For"
                    pairClass="description"
                    value={
                      isActive || isCompleted
                        ? () => (
                            <EditPaymentFor
                              value={{
                                title: paymentPage.title,
                                description: paymentPage.description,
                              }}
                              entityId={paymentPage.id}
                              editFn={this.editPaymentPage}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () => (
                            <div>
                              {paymentPage.title}
                              {paymentPage.description && (
                                <div
                                  class="label--secondary"
                                  style={{ whiteSpace: 'pre' }}
                                >
                                  {paymentPage.description}
                                </div>
                              )}
                            </div>
                          )
                    }
                  />

                  <EntityDetailRow
                    label="Receipt"
                    value={
                      isActive || isCompleted
                        ? () => (
                            <EditReceipt
                              value={paymentPage.receipt}
                              entityId={paymentPage.id}
                              editFn={this.editPaymentPage}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : paymentPage.receipt || '--'
                    }
                  />

                  <EntityDetailRow label="Created by">
                    {!!paymentPage.user ? (
                      <Definition>
                        {paymentPage.user.name}
                        {paymentPage.user.email}
                      </Definition>
                    ) : (
                      'API'
                    )}
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Created At"
                    value={() => <Time value={paymentPage.created_at} />}
                  />

                  <EntityDetailRow
                    label={isExpired ? 'Expired On' : 'Expires On'}
                    value={
                      isActive || isCompleted
                        ? () => (
                            <EditExpiry
                              value={paymentPage.expire_by}
                              editFn={this.editPaymentPage}
                              entityId={paymentPage.id}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () => (
                            <Time
                              value={paymentPage.expire_by}
                              format="DD MMM YYYY, hh:mm a"
                            />
                          )
                    }
                  />

                  <EntityDetailRow
                    label="Times Payable"
                    value={
                      isActive || isCompleted
                        ? () => (
                            <EditTimesPayable
                              value={paymentPage.times_payable}
                              editFn={this.editPaymentPage}
                              entityId={paymentPage.id}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () =>
                            paymentPage.times_payable || (
                              <div class="text-danger">No Limit</div>
                            )
                    }
                  />

                  <EntityDetailRow
                    label="Notes"
                    value={() => (
                      <EditNotes
                        value={paymentPage.notes}
                        editFn={this.editPaymentPage}
                        entityId={paymentPage.id}
                        trackerFn={trackDetailViewEdits}
                      />
                    )}
                  />

                  <GroupDetailsTable
                    title="Successful Payments"
                    subTitle={
                      <React.Fragment>
                        <Amount
                          value={paymentPage.total_amount_paid}
                          currency={paymentPage.currency}
                        />{' '}
                        total sales
                      </React.Fragment>
                    }
                    class="paymentpage-link-payments-table"
                    loading={paymentsListLoading}
                    items={paymentPagePayments}
                    rowConfig={[
                      [
                        data => (
                          <span class="label--primary">{data.contact}</span>
                        ),
                        data => (
                          <NavLink
                            class="btn-link no-padding"
                            to={`/payments/${data.id}`}
                            target="_blank"
                          >
                            {data.id}
                          </NavLink>
                        ),
                      ],
                      [
                        data => (
                          <span class="label--secondary">{data.email}</span>
                        ),
                        data => (
                          <span class="label--secondary">
                            <Time
                              value={data.created_at}
                              format="DD MMM YYYY, hh:mm:ss a"
                            />
                          </span>
                        ),
                      ],
                    ]}
                    loaderConfig={[
                      [{ width: '70%' }, { width: '45%' }],
                      [{ width: '60%', height: '10px' }, { width: '30%' }],
                    ]}
                  />
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    );
  }
}
