import React from 'react';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import DataTable from 'common/ui/Table/DataTable';
import LoaderDots from 'common/ui/LoaderDots';
import { isOrgFeatureExist } from 'merchant/models/User';
import {
  refundId,
  amount,
  refundSpeed,
  refundStatus as refundStatusPair,
} from 'common/ui/item/pair';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { selfServerTrack } from 'merchant/views/Transactions/AnalyticsTrack';

/*
 * Design:
 * https://projects.invisionapp.com/d/main#/console/11691503/246774373/preview
 * Inputs:
 * @param {Object} payment
 *
 * Descrition:
 * Given `payment` parameter exactly the same as fetch payments api , this
 * will display refund status and actions
 */

/**
 * Gateway Refund Not Supported
 *
 * optimizer_provider === "" for razorpay payment
 * optimizer_provider !== "" for optimizer payment
 * gateway_refund_support dependent on gateway config
 */

const NumRefunds = ({ refunds, titleCase = false }) => {
  const refundItems = refunds.items || [];

  const numRefunds = refundItems.length;
  const refundSuffix = numRefunds === 0 || numRefunds > 1 ? 's' : '';

  return (
    <span>
      {refunds.loading ? <LoaderDots /> : numRefunds} {titleCase ? 'R' : 'r'}efund{refundSuffix}
    </span>
  );
};

const RefundsList = ({ refunds, onToggleClick = () => {} }) => {
  const columns = [refundId, amount];
  columns.splice(1, 0, refundSpeed);
  columns.push(refundStatusPair);

  return refunds && refunds?.items?.length > 0 ? (
    <ContentToggler
      onToggleClick={() => {
        onToggleClick(refunds.items[0]?.speed_requested);
      }}
    >
      <span>Refund Details</span>
      <div className="full-width-item sub-entity-list">
        <DataTable
          customClass="refunds-table"
          progressLoader={true}
          title="Refunds"
          columns={columns}
          items={refunds.items}
          loading={refunds.loading}
          showHeaders={true}
          noStripe={true}
          onCellClick={selfServerTrack}
        />
      </div>
    </ContentToggler>
  ) : null;
};

const RefundDetails = ({ items = [] }) => {
  const refundReason = items[0]?.notes?.refund_reason;
  const refundRefNumber = items[0]?.acquirer_data?.rrn || items[0]?.acquirer_data?.arn;

  return (
    <>
      <Definition customClass="m-t">
        <span>Refund Reason</span>
        <span>{refundReason || '--'}</span>
      </Definition>
      <Definition customClass="m-t">
        <span>Refund Reference Number</span>
        <span>{refundRefNumber || '--'}</span>
      </Definition>
    </>
  );
};

const RefundDefinition = ({ refundStatus, payment, refunds, gatewayRefundNotSupported }) => {
  const { amount_refunded: amountRefunded, currency } = payment;

  if (refundStatus === 'partial') {
    return (
      <Definition>
        <span>
          <Amount value={amountRefunded} currency={currency} /> Refunded
        </span>
        <span>
          Partially refunded in <NumRefunds refunds={refunds} />
        </span>
      </Definition>
    );
  }

  // TODO: handle text 'Paytm' to be dynamic when extending this feature for other payment providers
  if (gatewayRefundNotSupported) {
    return (
      <Definition>
        <span>
          We currently do not support refunds for Paytm &apos;Instant (beta)&apos; integration. You
          can process this refund from your{' '}
          <a
            className="visit-link"
            href="https://dashboard.paytm.com/"
            target="_blank"
            rel="noopener noreferrer"
          >
            Paytm Business Dashboard <i className="i i-redirect" />
          </a>
        </span>
      </Definition>
    );
  }

  return <Definition>No refunds issued yet</Definition>;
};

const IssueRefund = ({ refundStatus, payment, onRefundStatusClick, gatewayRefundNotSupported }) => {
  const { gateway_refund_support: gatewayRefundSupport } = payment;

  if (gatewayRefundNotSupported) return null;

  const hasOpenNonFraudDisputes =
    payment?.disputes?.items?.filter(
      ({ status, phase }) => ['open', 'under_review'].indexOf(status) > -1 && phase !== 'fraud',
    )?.length ?? 0;

  return (
    <>
      <button
        type="button"
        className="btn btn-default"
        onClick={onRefundStatusClick}
        disabled={hasOpenNonFraudDisputes || !gatewayRefundSupport}
      >
        {refundStatus === 'partial' ? 'Issue another Refund' : 'Issue Refund'}
      </button>
      {Boolean(hasOpenNonFraudDisputes) && (
        <p className="text-danger">
          Refunds are disabled as there {hasOpenNonFraudDisputes > 1 ? 'are ' : 'is an '} open&nbsp;
          {hasOpenNonFraudDisputes > 1 ? 'disputes' : 'dispute'} on this payment
        </p>
      )}
    </>
  );
};

const PaymentRefund = ({
  payment,
  refunds,
  openRefundModal,
  onToggleClick = () => {},
  isQrCode = false,
}) => {
  const { status: paymentStatus, refund_status: refundStatus, error_reason: errorReason } = payment;

  const gatewayRefundNotSupported =
    Boolean(payment?.optimizer_provider) && !payment?.gateway_refund_support;

  const onRefundStatusClick = () => {
    analyticsTrack({
      objectName: isQrCode ? 'qr payment detail refund issued' : 'action items on sidebar',
      actionName: 'clicked',
      screen: isQrCode ? 'qrcode payment detail' : 'home page',
      properties: payment.analyticsPayload(),
    });
    return openRefundModal();
  };

  if (['created', 'authorized', 'failed'].indexOf(paymentStatus) >= 0) {
    return (
      <Definition>
        <span>Not Applicable</span>
        <span>Only captured payments can be refunded.</span>
      </Definition>
    );
  } else if (paymentStatus === 'captured') {
    return (
      <div className="payment-refund--captured">
        <ShowWhen
          additionalCondition={(user) =>
            !user.isRefundAllowed ||
            user.isOrgAllowedFunctionality('card_refunds') ||
            !(['card', 'emi'].indexOf(payment.method) !== -1)
          }
        >
          <div className="m-b">
            <RefundDefinition
              refundStatus={refundStatus}
              payment={payment}
              refunds={refunds}
              gatewayRefundNotSupported={gatewayRefundNotSupported}
            />
          </div>
        </ShowWhen>
        <ShowWhen
          additionalCondition={(user) =>
            !isOrgFeatureExist('block_payment_refund') &&
            user.isRefundAllowed &&
            (user.isOrgAllowedFunctionality('card_refunds') ||
              ['card', 'emi'].indexOf(payment.method) === -1)
          }
        >
          <IssueRefund
            refundStatus={refundStatus}
            payment={payment}
            onRefundStatusClick={onRefundStatusClick}
            gatewayRefundNotSupported={gatewayRefundNotSupported}
          />
        </ShowWhen>
        <ShowWhen
          additionalCondition={(user) =>
            user.isRefundAllowed &&
            !user.isOrgAllowedFunctionality('card_refunds') &&
            ['card', 'emi'].indexOf(payment.method) > -1
          }
        >
          Refunds cannot be created for Card transactions
        </ShowWhen>
        {refundStatus === 'partial' && (
          <RefundsList
            refunds={refunds}
            onToggleClick={() => {
              onToggleClick(payment);
            }}
          />
        )}
      </div>
    );
  } else if (paymentStatus === 'refunded') {
    const isDeductAtOnset =
      payment?.disputes.items.filter((disp) => {
        return disp.status !== 'lost' && disp.amount_deducted > 0;
      }).length > 0;

    if (!refundStatus) {
      // Un captured refunds will be auto refunded
      return (
        <Definition>
          <span>Auto Refunded</span>
          <span>
            Payment was not captured within 5 days of creation, hence it was automatically refunded.
          </span>
        </Definition>
      );
    } else if (refundStatus === 'full') {
      return (
        <div>
          <Definition>
            <span>Fully Refunded</span>
            {isDeductAtOnset ? (
              <span>
                This is a temporary debit. It will be reversed after the issuing bank closes the
                chargeback in your favor.
              </span>
            ) : (
              <span>
                Fully Refunded in <NumRefunds refunds={refunds} />
              </span>
            )}
          </Definition>
          {errorReason === 'avs_failure' && (
            <Definition customClass="m-t">
              <span>Refund Reason</span>
              <span>Payment auto refunded because of billing address mismatch</span>
            </Definition>
          )}
          <ShowWhen additionalCondition={(user) => user.isPaymentsExtraRefundDetailsEnabled}>
            <RefundDetails items={refunds.items} />
          </ShowWhen>
          <div className="m-t" />
          {
            <RefundsList
              refunds={refunds}
              onToggleClick={(speedRequested) => {
                onToggleClick(payment, speedRequested);
              }}
            />
          }
        </div>
      );
    }
  }

  return null;
};

export default PaymentRefund;
