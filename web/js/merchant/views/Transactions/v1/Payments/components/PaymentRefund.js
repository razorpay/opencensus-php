import React from 'react';
import moment from 'moment';

import { SelfServeActionPages } from 'common/constant/enums';
import Amount from 'common/ui/Amount';
import Definition from 'common/ui/Definition';
import LoaderDots from 'common/ui/LoaderDots';
import DataTable from 'common/ui/Table/DataTable';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import { amount, refundId, refundSpeed, enchancedRefundStatus } from 'common/ui/item/pair';
import { analyticsTrack } from 'common/utils/analytics';
import ShowWhen from 'merchant/components/ShowWhen';
import { SEAMLESS_PROVIDERS } from 'merchant/views/Navigator/constants';
import { REFUND_STATUSES } from 'merchant/views/Transactions/v1/Payments/constants';
import { makeIdLink } from 'merchant/views/Transactions/v1/Refunds/Utils';
import { getInitiatePointAndPageAndScreenName } from 'merchant/views/Transactions/v1/utils';

import IssueRefund from './IssueRefund';

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

const _refundId = (initiatePage = SelfServeActionPages.TransactionsPayments) => {
  return {
    title: refundId.title,
    value: (item) => {
      const intermediateElement = makeIdLink('refund')(item, initiatePage, 'payment-details');
      return <div>{intermediateElement}</div>;
    },
  };
};

const RefundsList = ({ refunds, isOptimizerView = false, onToggleClick = () => {} }) => {
  const { initiatePage } = getInitiatePointAndPageAndScreenName();
  const columns = [_refundId(initiatePage), amount];
  columns.splice(1, 0, refundSpeed);
  columns.push(enchancedRefundStatus(isOptimizerView));

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
  const refundInProgress =
    payment?.status !== 'refunded' && payment?.notes?.refund_status === REFUND_STATUSES.PROCESSING;

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

  return (
    <Definition>{refundInProgress ? 'Refund is in progress' : 'No refunds issued yet'}</Definition>
  );
};

const PaymentRefund = ({
  payment,
  refunds,
  isOptimizerView,
  openRefundModal,
  collectEzetapKeys,
  onToggleClick = () => {},
  isQrCode = false,
  fetchEzetapKeys,
}) => {
  const { status: paymentStatus, refund_status: refundStatus, error_reason: errorReason } = payment;

  /****************************************************************************************************************
   * TODO: Temporary changes. To be removed after permanent fix from backend.
   * Slack thread: https://razorpay.slack.com/archives/C01F7R2ULAH/p1677734285481029
   */

  // Get the number of months since the timestamp
  const monthsAgo = moment().diff(moment.unix(payment?.created_at), 'months'); // number of months as an integer

  // Check if the timestamp is more than 180 days / 6 months old
  const lteSixMonths = monthsAgo <= 6; // true or false

  /****************************************************************************************************************/

  const gatewayRefundNotSupported =
    lteSixMonths &&
    SEAMLESS_PROVIDERS.includes(payment?.optimizer_provider) &&
    !payment?.gateway_refund_support;

  const onRefundStatusClick = () => {
    analyticsTrack({
      objectName: isQrCode ? 'qr payment detail refund issued' : 'action items on sidebar',
      actionName: 'clicked',
      screen: isQrCode ? 'qrcode payment detail' : 'home page',
      properties: payment.analyticsPayload(),
    });
    return openRefundModal();
  };

  if (
    paymentStatus !== 'refunded' &&
    payment?.notes?.refund_status === REFUND_STATUSES.PROCESSING
  ) {
    return <Definition>Refund is in Progress</Definition>;
  } else if (['created', 'authorized', 'failed'].indexOf(paymentStatus) >= 0) {
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
          additionalCondition={(user, session) =>
            !(session?.org?.features?.indexOf('block_payment_refund') > -1) &&
            user.isRefundAllowed &&
            (user.isOrgAllowedFunctionality('card_refunds') ||
              ['card', 'emi'].indexOf(payment.method) === -1)
          }
        >
          <IssueRefund
            refundStatus={refundStatus}
            payment={payment}
            onRefundStatusClick={onRefundStatusClick}
            collectEzetapKeys={collectEzetapKeys}
            gatewayRefundNotSupported={gatewayRefundNotSupported}
            fetchEzetapKeys={fetchEzetapKeys}
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
            isOptimizerView={isOptimizerView}
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
          <RefundsList
            refunds={refunds}
            isOptimizerView={isOptimizerView}
            onToggleClick={(speedRequested) => {
              onToggleClick(payment, speedRequested);
            }}
          />
        </div>
      );
    }
  }

  return null;
};

export default PaymentRefund;
