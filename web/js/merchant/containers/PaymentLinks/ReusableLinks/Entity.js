import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';

import { updateRPLInReduxList } from 'merchant/modules/invoices/list';

import {
  fetchReusableLinksEntity,
  fetchReusableLinkPaymentsList,
  editReusableLink,
  sendLink,
} from './model';
import { ReusableLinksStatusLabel } from 'merchant/components/StatusLabel';
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
import { trackDetailViewEdits } from '../Links/ga';

import EditPaymentFor from './Edit/EditPaymentFor';
import EditTimesPayable from './Edit/EditTimesPayable';

import { EditExpiry, EditNotes, EditReceipt } from '../Edit/index';
import ActivateAgain from './Modals/ActivateAgain';
import ShareView from './Modals/Share';

import Button from 'component/Button';

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'Max Times payable limit is completed',
  expired: 'The link is expired',
  cancelled: 'You closed the link',
};

@connect(null, {
  updateRPLInReduxList,
  showNotification,
  openModal,
  closeModal,
})
export default class ReusableLinksEntity extends React.Component {
  state = {
    reusableLink: {},
    reusableLinkPayments: [],
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

    return fetchReusableLinksEntity(id)
      .then(resp => {
        if (resp) {
          this.setState({ reusableLink: resp.data });
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

  getStatsTable(reusableLink) {
    return [
      [
        { title: 'Payments Made', value: reusableLink.times_paid },
        {
          title: 'Total Sales',
          value: (
            <Amount
              value={reusableLink.total_amount_paid}
              currency={reusableLink.currency}
            />
          ),
        },
      ],
    ];
  }

  fetchEntityPayments(id) {
    return fetchReusableLinkPaymentsList(id)
      .then(resp => {
        if (resp) {
          this.setState({ reusableLinkPayments: resp.data.items });
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

  onCopy = ({ reusableLinkId }) => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Reusable Payment Links',
      eventAction: 'Copy - Reusable Payment Link',
      eventLabel: `payment_link_id=${reusableLinkId}`,
    });
  };

  openShareView = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          handleClick={this.sendLink}
          handleAction={sendLink.bind(null, this.state.reusableLink.id)}
          url={this.state.reusableLink.short_url}
          showNotification={this.props.showNotification}
        />
      ),
    });
  };

  reActivateLink = () => {
    let statusReason = this.state.reusableLink.status_reason;

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';
    const isCancelled = statusReason.toLowerCase() === 'cancelled';

    if (isCancelled) {
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
              ? this.state.reusableLink.expire_by
              : undefined
          }
          timesPayable={
            isCompleted ? this.state.reusableLink.times_payable : undefined
          }
          handleClose={this.props.closeModal}
          handleClick={this.editReusableLink()}
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

    const status = this.state.reusableLink.status;
    const statusReason = this.state.reusableLink.status_reason;

    const isActive = status === 'active';
    const isCancelled =
      statusReason && statusReason.toLowerCase() === 'cancelled';

    let header, message, affirmativeLabel, affirmativePendingLabel, successMsg;

    if (isActive) {
      /* Wants manual deactivation */

      header = 'Deactivate Link?';
      message =
        'Once you deactivate the link, you will not be able to accept payments till you activate it again.';
      affirmativeLabel = 'Yes, deactivate';
      affirmativePendingLabel = 'Deactivating..';
      successMsg = `${this.state.reusableLink.id} link is now inactive`;
    } else if (isCancelled) {
      /* Wants activation for manual deactivation for cancelled status */

      header = 'Activate Link?';
      message =
        'Once you activate the link, you will be able to accept payments.';
      affirmativeLabel = 'Yes, activate';
      affirmativePendingLabel = 'Activating..';
      successMsg = `${this.state.reusableLink.id} link is now active`;
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
        // TODO: Where is api to manually activate/deactivate?
        return editReusableLink(this.state.reusableLink.id, {
          status: newStatus,
        })
          .then(resp => {
            this.props.showNotification({
              type: 'success',
              message: successMsg,
            });

            this.props.closeModal();

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
              err = `Some Network error occured`;
            }

            this.props.showNotification({
              type: 'error',
              message: err,
            });
          });
      },
    });
  };

  editReusableLink = () => {
    const self = this;

    return function(data) {
      return editReusableLink(self.state.reusableLink.id, data)
        .then(resp => {
          if (resp.data) {
            self.props.updateRPLInReduxList(resp.data, false);

            self.props.showNotification({
              type: 'success',
              message: `${self.state.reusableLink.id} successfully Updated`,
            });

            self.setState({
              reusableLink: resp.data,
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
            err = `Some Network error occured`;
          }

          self.props.showNotification({
            type: 'error',
            message: err,
          });
        });
    };
  };

  render() {
    let {
      reusableLink,
      loading,
      reusableLinkPayments,
      paymentsListLoading,
    } = this.state;

    let status = reusableLink.status;
    let statusReason = reusableLink.status_reason;

    const isActive = !loading && status === 'active';
    const isExpired =
      !loading && !isActive && statusReason.toLowerCase() === 'expired';

    const isCompleted =
      !loading && !isActive && statusReason.toLowerCase() === 'completed';

    const isSmsOrEmailSent =
      reusableLink.sms_status === 'sent' ||
      reusableLink.email_status === 'sent';

    return (
      <div class="content-wrapper content-sm txn-details Entity--reusable">
        {loading ? (
          <div class="page-spinner-container">
            <Spinner />
          </div>
        ) : (
          <div class="panel panel-default SliderPanel">
            <div class="panel-heading">
              <i class="i i-link text-primary icon--formal" />{' '}
              <strong>{reusableLink.id}</strong>
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
                  <StatsInfo stats={this.getStatsTable(reusableLink)} />
                  <EntityDetailRow
                    label="Amount"
                    value={() => (
                      <Amount
                        value={reusableLink.amount}
                        currency={reusableLink.currency}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Link URL"
                    value={() => (
                      <CopyLink
                        url={reusableLink.short_url}
                        onCopy={() => {
                          this.onCopy({
                            reusableLinkId: reusableLink.id,
                          });
                        }}
                      />
                    )}
                  />
                  <EntityDetailRow
                    label="Status"
                    value={() => (
                      <div>
                        <ReusableLinksStatusLabel status={status} />
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
                                title: reusableLink.title,
                                description: reusableLink.description,
                              }}
                              entityId={reusableLink.id}
                              editFn={this.editReusableLink()}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () => (
                            <div>
                              {reusableLink.title}
                              {reusableLink.description && (
                                <div
                                  class="label--secondary"
                                  style={{ whiteSpace: 'pre' }}
                                >
                                  {reusableLink.description}
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
                              value={reusableLink.receipt}
                              entityId={reusableLink.id}
                              editFn={this.editReusableLink()}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : reusableLink.receipt || '--'
                    }
                  />

                  <EntityDetailRow label="Created by">
                    {!!reusableLink.user ? (
                      <Definition>
                        {reusableLink.user.name}
                        {reusableLink.user.email}
                      </Definition>
                    ) : (
                      'API'
                    )}
                  </EntityDetailRow>

                  <EntityDetailRow
                    label="Created At"
                    value={() => <Time value={reusableLink.created_at} />}
                  />

                  <EntityDetailRow
                    label={isExpired ? 'Expired On' : 'Expires On'}
                    value={
                      isActive || isCompleted
                        ? () => (
                            <EditExpiry
                              value={reusableLink.expire_by}
                              editFn={this.editReusableLink()}
                              entityId={reusableLink.id}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () => (
                            <Time
                              value={reusableLink.expire_by}
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
                              value={reusableLink.times_payable}
                              editFn={this.editReusableLink()}
                              entityId={reusableLink.id}
                              trackerFn={trackDetailViewEdits}
                            />
                          )
                        : () => (
                            <div
                              value={reusableLink.times_payable || 'No Limit'}
                            />
                          )
                    }
                  />

                  <EntityDetailRow
                    label="Notes"
                    value={() => (
                      <EditNotes
                        value={reusableLink.notes}
                        editFn={this.editReusableLink()}
                        entityId={reusableLink.id}
                        trackerFn={trackDetailViewEdits}
                      />
                    )}
                  />

                  <GroupDetailsTable
                    title="Successful Payments"
                    subTitle={
                      <React.Fragment>
                        <Amount
                          value={reusableLink.total_amount_paid}
                          currency={reusableLink.currency}
                        />{' '}
                        total sales
                      </React.Fragment>
                    }
                    class="reusable-link-payments-table"
                    loading={paymentsListLoading}
                    items={reusableLinkPayments}
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
