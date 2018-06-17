import { NavLink } from 'react-router-dom';
import { connect } from 'react-redux';

import { updateRPLInReduxList } from 'merchant/modules/invoices/list';

import {
  editReusableLink,
  fetchReusableLinksEntity,
  fetchReusableLinkPaymentsList,
} from './model';
import { ReusableLinksStatusLabel } from 'merchant/components/StatusLabel';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Spinner from 'rzp/ui/Spinner';
import Time from 'rzp/ui/Time';
import Amount from 'rzp/ui/Amount';
import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import StatsInfo from 'ui/StatsTable';
import GroupDetailsTable from 'rzp/ui/GroupDetailsTable';

import { closeModal, openModal } from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import { trackDetailViewEdits } from '../Links/ga';

import EditPaymentFor from './Edit/EditPaymentFor';
import EditTimesPayable from './Edit/EditTimesPayable';

import { EditExpiry, EditNotes, EditReceipt } from '../Edit/index';
import ActivateAgainModal from './ActivateAgainModal';

import Button from 'component/Button';

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

  reActivateLink = () => {
    let statusReason = this.state.reusableLink.status_reason;
    statusReason = 'expired';

    const isExpired = statusReason.toLowerCase() === 'expired';
    const isCompleted = statusReason.toLowerCase() === 'completed';
    const isCancelled = statusReason.toLowerCase() === 'cancelled'; // TODO: API to activate manually closed link?

    const currentTimeStamp = moment().unix();
    // Ideally, it should consider 2 min window, because it would take time for merchant to update.
    const hasExpiredInCompletedState =
      this.props.expire_by && this.props.expire_by < currentTimeStamp;

    this.props.openModal({
      size: 'medium',
      component: (
        <ActivateAgainModal
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

  deactivateLink = () => {
    const newStatus = 'closed';

    this.context.confirm({
      header: 'Deactivate Link?',
      message: () => (
        <div class="text-semi-muted">
          <p>
            Are you sure you want to deactivate the link?
            <br />
            Payments will no longer be accepted for this link.
          </p>
        </div>
      ),
      affirmativeLabel: 'Yes, proceed',
      affirmativePendingLabel: 'Deactivating..',
      abortLabel: "No, don't!",
      action: () => {
        // TODO: Where is api to manually deactivate?
        return editReusableLink(this.state.reusableLink.id, {
          status: newStatus,
        })
          .then(() => {
            this.props.showNotification({
              type: 'success',
              message: `${this.props.reusableLink.id} link is now inactive`,
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
                      <span class="CopyLink">
                        <span>{reusableLink.short_url}</span>
                        <CustomClipboard
                          value={reusableLink.short_url}
                          onCopy={this.onCopy({
                            reusableLinkId: reusableLink.id,
                          })}
                        >
                          <button class="btn btn-default btn-xs">copy</button>
                        </CustomClipboard>
                      </span>
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
                            isActive ? this.deactivateLink : this.reActivateLink
                          }
                        >
                          {isActive ? 'Deactivate Link' : 'Activate Link'}
                        </Button.Transparent>
                        <div class="text-danger">{statusReason}</div>
                      </div>
                    )}
                  />
                  <EntityDetailRow
                    label="Payment For"
                    pairClass="description"
                    value={
                      isActive
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
                      isActive
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
                      isActive
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
                      isActive
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
