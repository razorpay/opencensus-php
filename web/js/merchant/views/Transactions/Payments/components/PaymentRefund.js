import React from 'react';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import DataTable from 'common/ui/Table/DataTable';
import LoaderDots from 'common/ui/LoaderDots';
import {
  refundId,
  amount,
  refundSpeed,
  refundStatus as refundStatusPair,
} from 'common/ui/item/pair';
import ShowWhen from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';

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
      <div class="full-width-item sub-entity-list">
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

const PaymentRefund = ({
  payment,
  refunds,
  openRefundModal,
  onToggleClick = () => {},
  isQrCode = false,
}) => {
  const paymentStatus = payment.status;
  const refundStatus = payment.refund_status;
  const refundAmount = payment.amount_refunded;
  const currency = payment.currency;
  const errorReason = payment.error_reason;

  const onRefundStatusClick = () => {
    if (isQrCode) {
      analyticsTrack({
        objectName: 'qr payment detail refund issued',
        actionName: 'clicked',
        screen: 'qrcode payment detail',
        properties: payment.analyticsPayload(),
      });
    }
    analyticsTrack({
      objectName: 'action items on sidebar',
      actionName: 'clicked',
      screen: 'home page',
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
    const hasOpenNonFraudDisputes =
      payment.disputes &&
      payment.disputes.items.filter(
        ({ status, phase }) => ['open', 'under_review'].indexOf(status) > -1 && phase !== 'fraud',
      ).length;
    return (
      <div>
        <ShowWhen
          additionalCondition={(user) =>
            !user.isRefundAllowed ||
            user.isOrgAllowedFunctionality('card_refunds') ||
            !(['card', 'emi'].indexOf(payment.method) !== -1)
          }
        >
          <div class="m-b">
            {refundStatus === 'partial' ? (
              <Definition>
                <span>
                  <Amount value={refundAmount} currency={currency} /> Refunded
                </span>
                <span>
                  Partially refunded in{' '}
                  <NumRefunds
                    refunds={refunds}
                    onToggleClick={() => {
                      onToggleClick(payment);
                    }}
                  />
                </span>
              </Definition>
            ) : (
              <Definition>No refunds issued yet</Definition>
            )}
          </div>
        </ShowWhen>
        <ShowWhen
          additionalCondition={(user) =>
            user.isRefundAllowed &&
            (user.isOrgAllowedFunctionality('card_refunds') ||
              ['card', 'emi'].indexOf(payment.method) === -1)
          }
        >
          <p>
            <button
              class="btn btn-default"
              onClick={onRefundStatusClick}
              disabled={hasOpenNonFraudDisputes}
            >
              {refundStatus === 'partial' ? 'Issue another Refund' : 'Issue Refund'}
            </button>
          </p>
          {hasOpenNonFraudDisputes ? (
            <span class="text-danger">
              Refunds are disabled as there {hasOpenNonFraudDisputes > 1 ? 'are ' : 'is an '} open
              dispute{hasOpenNonFraudDisputes > 1 && 's'} on this payment
            </span>
          ) : null}
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
          <div class="m-t" />
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
