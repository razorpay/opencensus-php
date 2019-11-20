import { Link } from 'react-router-dom';
import { connect } from 'react-redux';

import { updatePPInReduxList } from 'merchant/reducers/invoices/list';
import { keysToSentence } from 'common/utils/rzp-utils';

import { sendLink } from '../model';
import { PaymentPagesStatusLabel } from 'merchant/components/StatusLabel';
import Definition from 'common/ui/Definition';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import Time from 'common/ui/Time';
import Amount from 'common/ui/Amount';
import CopyLink from 'merchant/components/CopyLink';
import StatsInfo from 'common/ui/StatsTable';
import { getKeysSeparatedByPipe } from 'common/utils/rzp-utils';

import { closeModal, openModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { trackDetailViewEdits, trackShareActions } from '../ga';

import EditStock from 'merchant/views/PaymentPages/PaymentPages/components/EditStock';

import {
  EditExpiry,
  EditNotes,
} from 'merchant/containers/PaymentLinks/Edit/index';
import ShareView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/Share';
import PPEmbedButtonView from 'merchant/views/PaymentPages/PaymentPages/components/Modals/EmbedButton';

import Button from 'common/new-ui/Button';

const MAX_API_COUNT = 100;

/* Human readable reason to be displayed */
const inActiveStatusReasonMap = {
  completed: 'All the available units are sold',
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

  openEmbedButtonView = () => {
    this.props.openModal({
      size: 'small',
      component: (
        <PPEmbedButtonView
          handleClose={this.props.closeModal}
          trackerFn={function() {}}
          url={this.props.paymentPageEntity.short_url}
          color={this.props.merchantColor}
        />
      ),
    });
  };

  openShareView = () => {
    const { paymentPageEntity } = this.props;
    trackDetailViewEdits('Click Share');
    this.props.openModal({
      size: 'small',
      component: (
        <ShareView
          handleClose={this.props.closeModal}
          openModal={this.props.openModal}
          handleAction={sendLink.bind(null, paymentPageEntity.id)}
          showNotification={this.props.showNotification}
          url={paymentPageEntity.short_url}
          title={paymentPageEntity.title}
          description={paymentPageEntity.description}
          trackerFn={trackShareActions}
          openEmbedButton={this.openEmbedButtonView}
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

    const paymentPageItem = paymentPageEntity.payment_page_items[0];

    return (
      <div class="content-wrapper content-sm txn-details Entity--paymentpage Entity--paymentpage-v2">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="i i-payment-pages text-primary icon--formal" />{' '}
            <div class="text">{paymentPageEntity.title}</div>
            <div class="btn-toolbar pull-right">
              {isRoleAllowedEdit && (
                <Link
                  class="btn Button--primary--invert btn-sm"
                  to={`/paymentpages/${paymentPageEntity.id}/edit`}
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

                {/* For dynamic amount */}
                {!paymentPageItem.item.amount && (
                  <EntityDetailRow
                    label="Units Sold"
                    value={paymentPageItem.quantity_sold}
                  />
                )}

                {/* For fixed amount */}
                {!!paymentPageItem.item.amount && (
                  <EntityDetailRow
                    label="Units Sold"
                    value={() => (
                      <EditStock
                        totalStock={paymentPageItem.stock}
                        quantitySold={paymentPageItem.quantity_sold}
                        editFn={editPaymentPage}
                        paymentPageItemId={paymentPageItem.id}
                        trackerFn={trackDetailViewEdits}
                        isRoleAllowedEdit={isRoleAllowedEdit}
                      />
                    )}
                  />
                )}

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
                  label="Created On"
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
