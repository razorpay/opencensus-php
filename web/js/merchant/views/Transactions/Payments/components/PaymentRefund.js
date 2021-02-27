import React from 'react';

import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import DataTable from 'common/ui/Table/DataTable';
import LoaderDots from 'common/ui/LoaderDots';
import PlaceholderLoader from 'common/ui/PlaceholderLoader';
import { refundId, amount, createdAt, refundSpeed, refundStatus } from 'common/ui/item/pair';
import ShowWhen, { showWhenUtil } from 'merchant/components/ShowWhen';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

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

const createdAtWithStyle = { columnClass: 'text-right', ...createdAt };

const NumRefunds = ({ refunds, titleCase = false }) => {
  const refundItems = refunds.items || [];

  const numRefunds = refundItems.length,
    refundSuffix = numRefunds === 0 || numRefunds > 1 ? 's' : '';

  return (
    <span>
      {refunds.loading ? <LoaderDots /> : numRefunds} {titleCase ? 'R' : 'r'}efund{refundSuffix}
    </span>
  );
};

const RefundsList = ({ refunds, onToggleClick = () => {} }) => {
  const columns = [refundId, amount];
  columns.splice(1, 0, refundSpeed);
  columns.push(refundStatus);

  return (
    <ContentToggler
      onToggleClick={() => {
        onToggleClick(refunds.items[0].speed_requested);
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
  );
};

export default ({ payment, refunds, openRefundModal, onToggleClick = () => {} }) => {
  const paymentStatus = payment.status,
    refundStatus = payment.refund_status,
    refundAmount = payment.amount_refunded,
    currency = payment.currency;

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
              onClick={() => {
                analyticsTrack({
                  objectName: 'action items on sidebar',
                  actionName: 'clicked',
                  screen: 'home page',
                  properties: payment.analyticsPayload(),
                });
                return openRefundModal();
              }}
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
            <span>
              Fully Refunded in <NumRefunds refunds={refunds} />
            </span>
          </Definition>
          <p />
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
