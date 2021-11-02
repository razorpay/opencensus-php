import { Link, withRouter } from 'react-router-dom';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import Spinner from 'common/ui/Spinner';
import Alert from 'common/ui/Forms/Alert';
import Definition from 'common/ui/Definition';
import { PaymentStatusLabel } from 'merchant/components/StatusLabel';
import ShowWhen from 'merchant/components/ShowWhen';
import EntityDetailRow from 'merchant/components/EntityDetailRow';
import PaymentMethod from 'merchant/views/Transactions/Payments/components/PaymentMethod';
import PaymentProvider from 'merchant/views/Transactions/Payments/components/PaymentProvider';
import PaymentRefund from 'merchant/views/Transactions/Payments/components/PaymentRefund';
import PaymentTransfers from 'merchant/views/Transactions/Payments/components/PaymentTransfers';
import PaymentDisputes from './PaymentDisputes';
import PaymentReceipt from './PaymentReceipt';
import PaymentPageDetails from './PaymentPageDetails';
import PaymentSplitInItems from './PaymentSplitInItems';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import SettlementOverview from './SettlementOverview';
import AnnouncementBar from 'merchant/components/AnnouncementBar';
import SettlementInfo from 'merchant/views/Settlements/components/SettlementInfo';
import React, { useEffect } from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

function PaymentDetails(props) {
  const {
    payment,
    card,
    bankTransfer, //virtual account details
    upiTransfer, //virtual account details
    refunds,
    transfers,
    isLoading,
    openRefundModal,
    statusMsg = {},
    onRefundDetailsToggleClick = () => {},
    onUpdateReferenceId = () => {},
    isRoleAllowedEdit,
    viewSettlementOverview,
    user,
    settlement_amount,
    location,
  } = props;
  const isSettlementOnHold =
    (settlement_amount.data.no_settlement && settlement_amount.data.no_settlement.on_hold) || false;

  const isFromHomePage = location.state?.fromHomePage;

  useEffect(() => {
    if (payment.id) {
      analyticsTrack({
        objectName: 'payment details',
        actionName: 'fetched',
        screen: isFromHomePage ? 'home page' : 'transactions',
        properties: {
          ...payment.analyticsPayload(),
          ...getCommonAnalyticsProperties(window.rzp_user),
          location: 'Payments',
        },
      });
      analyticsTrack({
        objectName: 'payment details sidebar',
        actionName: 'rendered',
        screen: isFromHomePage ? 'home page' : 'transactions',
        properties: {
          ...payment.analyticsPayload(),
          ...getCommonAnalyticsProperties(window.rzp_user),
          location: 'Payments',
        },
      });
    }
  }, [payment]);

  return (
    <div class="content-wrapper content-sm txn-details">
      {isLoading ? (
        <div class="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            {props.onClose && (
              <button type="button" class="close close-secondary" onClick={props.onClose}>
                <i class="i i-close" />
              </button>
            )}
            Payment Id: <b>{payment.id}</b>
          </div>

          <div class="SliderPanel__Body">
            {payment.status === 'authorized' && isRoleAllowedEdit && (
              <div class="payments-manual-actions">
                <button
                  onClick={() => {
                    analyticsTrack({
                      objectName: 'capture payment',
                      actionName: 'clicked',
                      screen: isFromHomePage ? 'home page' : 'transactions',
                      properties: {
                        ...payment.analyticsPayload(),
                        ...getCommonAnalyticsProperties(window.rzp_user),
                        location: 'Payments',
                      },
                    });
                    analyticsTrack({
                      objectName: 'action items on sidebar',
                      actionName: 'clicked',
                      screen: isFromHomePage ? 'home page' : 'transactions',
                      properties: {
                        ...payment.analyticsPayload(),
                        location: 'payment sidebar',
                        ...getCommonAnalyticsProperties(window.rzp_user),
                      },
                    });
                    props.confirmCapture(payment);
                  }}
                  class="btn btn-primary"
                >
                  Capture Payment
                </button>
                <button
                  onClick={openRefundModal}
                  class="btn btn-primary"
                  style={{ marginLeft: '5px' }}
                >
                  Refund Payment
                </button>
              </div>
            )}

            <div class="panel-body">
              <Alert type={statusMsg.type} message={statusMsg.message} />
              <div class="list-group pair-row-container">
                <PaymentPageDetails payment={payment} />

                <EntityDetailRow label="Amount">
                  <b>
                    <Amount value={payment.amount} currency={payment.currency} />
                  </b>
                </EntityDetailRow>

                <EntityDetailRow label="Status">
                  <PaymentStatusLabel status={payment.status} />
                </EntityDetailRow>

                {payment.error_code && (
                  <EntityDetailRow label="Error">
                    <Definition>
                      <span>{payment.error_code}</span>
                      {payment.error_description && <span>{payment.error_description}</span>}
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_source && (
                  <EntityDetailRow label="Error Source">
                    <Definition>
                      <span>{payment.error_source}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_step && (
                  <EntityDetailRow label="Error Step">
                    <Definition>
                      <span>{payment.error_step}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                {payment.error_reason && (
                  <EntityDetailRow label="Error Reason">
                    <Definition>
                      <span>{payment.error_reason}</span>
                    </Definition>
                  </EntityDetailRow>
                )}

                <ShowWhen apiFeatureEnabled="Marketplace">
                  <EntityDetailRow label="Transfer">
                    <PaymentTransfers
                      payment={payment}
                      transfers={transfers}
                      onCreateTransfer={() => props.goToLink('transfers/new')}
                    />
                  </EntityDetailRow>
                </ShowWhen>

                {payment.method !== 'cod' && (
                  <EntityDetailRow label="Refunds">
                    <PaymentRefund
                      payment={payment}
                      refunds={refunds}
                      openRefundModal={openRefundModal}
                      onToggleClick={onRefundDetailsToggleClick}
                    />
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Payment Method">
                  <PaymentMethod
                    payment={payment}
                    card={card}
                    bankTransfer={bankTransfer}
                    upiTransfer={upiTransfer}
                  />
                </EntityDetailRow>

                {payment.provider && (
                  <EntityDetailRow label="Provider">
                    <PaymentProvider payment={payment} />
                  </EntityDetailRow>
                )}

                {payment.gateway_provider && (
                  <EntityDetailRow label="Gateway">{payment.gateway_provider}</EntityDetailRow>
                )}

                <EntityDetailRow label="Created At">
                  <Time value={payment.created_at} format="DD MMM YYYY, hh:mm:ss a" />
                </EntityDetailRow>
                <ShowWhen
                  additionalCondition={() =>
                    user.isUxRevampPhase2Enabled && payment.transaction && !isSettlementOnHold
                  }
                >
                  <EntityDetailRow label="Settlement Details">
                    <SettlementInfo data={payment} />
                  </EntityDetailRow>
                </ShowWhen>
                <EntityDetailRow label="Description">{payment.description}</EntityDetailRow>

                <EntityDetailRow label="Disputes">
                  {payment.disputes && payment.disputes.count ? (
                    <PaymentDisputes
                      disputes={payment.disputes.items}
                      onDisputeClick={props.goToLink}
                    />
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>

                <EntityDetailRow label="Customer">
                  <Definition placeholder="No customer linked">
                    {payment.email}
                    {payment.contact}
                  </Definition>
                </EntityDetailRow>

                <EntityDetailRow label="Total Fee">
                  <Definition>
                    <Amount value={payment.fee} />
                    <span>
                      Razorpay Fee - <Amount value={payment.fee - payment.tax} currency="INR" />
                    </span>
                    <span>
                      GST - <Amount value={payment.tax} currency="INR" />
                    </span>
                  </Definition>
                </EntityDetailRow>

                <EntityDetailRow label="Fee Bearer">
                  <Definition>
                    {payment.fee_bearer === 'platform'
                      ? 'You are the fee bearer for this payment'
                      : 'The customer has paid the fees for this payment'}
                  </Definition>
                </EntityDetailRow>

                <ShowWhen
                  additionalCondition={() =>
                    user.isProjectNitroEnabled || user.isProjectNitroCorporateCard
                  }
                >
                  <AnnouncementBar
                    fromWhere="transactions"
                    url="https://lp.razorpay.com/razorpayxca-pymnts2"
                  />
                </ShowWhen>

                <EntityDetailRow label="Order ID">
                  {payment.order_id ? (
                    <Link to={`/orders/${payment.order_id}`}>
                      <code>{payment.order_id}</code>
                    </Link>
                  ) : (
                    '--'
                  )}
                </EntityDetailRow>

                {payment.invoice_id && (
                  <EntityDetailRow label="Invoice ID">
                    <Link to={`/invoices/${payment.invoice_id}`}>
                      <code>{payment.invoice_id}</code>
                    </Link>
                  </EntityDetailRow>
                )}

                <EntityDetailRow label="Notes">
                  {Object.keys(payment.notes).length
                    ? Object.keys(payment.notes).map((key, index) => (
                        <Definition key={index} customClass="notes">
                          {key}
                          {String(payment.notes[key] || '--')}
                        </Definition>
                      ))
                    : '--'}
                </EntityDetailRow>

                {user.isPaymentPageReceiptsEnabled && (
                  <PaymentReceipt payment={payment} onUpdateReferenceId={onUpdateReferenceId} />
                )}

                <PaymentSplitInItems payment={payment} />

                {!user.isUxRevampPhase2Enabled && payment.transaction ? (
                  <EntityDetailRow label="Settlement Details">
                    {payment.transaction.settlement ? (
                      <ContentToggler onToggleClick={viewSettlementOverview}>
                        <span>
                          Settled on{' '}
                          <Time value={payment.transaction.settled_at} format="DD MMM YYYY" />
                        </span>
                        <SettlementOverview payment={payment} />
                      </ContentToggler>
                    ) : payment.transaction.settled_at ? (
                      <span class="link">
                        To be settled on{' '}
                        <Time value={payment.transaction.settled_at} format="DD MMM YYYY" />
                      </span>
                    ) : (
                      '--'
                    )}
                  </EntityDetailRow>
                ) : null}
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default withRouter(PaymentDetails);
