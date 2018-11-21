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

import {
  EditExpiry,
  EditNotes,
  EditReceipt,
} from '../../../PaymentLinks/Edit/index';
import ShareView from '../Modals/Share';

import Button from 'component/Button';

const MAX_API_COUNT = 100;

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'All the available items in the Stock are sold',
  expired: 'The link is expired',
  deactivated: 'You manually deactivated the link',
};

@connect(state => ({ user: state.session.user }), {
  showNotification,
  openModal,
  closeModal,
})
export default class PaymentPagesV2Entity extends React.Component {
  getStatsTable(paymentPageEntity) {
    return [
      [
        {
          title: 'Number of Payments made',
          value: paymentPageEntity.captured_payments_count,
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

  openShareView = () => {
    const { paymentPageEntity } = this.props;
    trackDetailViewEdits('Click Share');
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          handleClick={this.sendLink}
          handleAction={sendLink.bind(null, paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          url={paymentPageEntity.short_url}
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          trackerFn={trackShareActions}
        />
      ),
    });
  };

  render() {
    let {
      createdByUser,
      paymentPageEntity,
      editPaymentPage,
      toggleManualActivation,
      reActivateLink,
    } = this.props;

    const isRoleAllowedEdit = this.props.user.isAllowedEdit('payment_pages');

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
                  value={
                    paymentPageEntity.amount
                      ? () => (
                          <Amount
                            value={paymentPageEntity.amount}
                            currency={paymentPageEntity.currency}
                          />
                        )
                      : '--'
                  }
                />
                <EntityDetailRow
                  label="Available Stock"
                  value={() => (
                    <EditStocks
                      value={paymentPageEntity.times_payable}
                      timesPaid={paymentPageEntity.times_paid}
                      editFn={editPaymentPage}
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
                            isActive ? toggleManualActivation : reActivateLink
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
                  {!!createdByUser ? (
                    <Definition>
                      {createdByUser.name}
                      {createdByUser.email}
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
                      editFn={editPaymentPage}
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
                      editFn={editPaymentPage}
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
                      editFn={editPaymentPage}
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
