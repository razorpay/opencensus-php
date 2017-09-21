import React from 'react';

import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import DataTable from 'rzp/ui/Table/DataTable';
import LoaderDots from 'rzp/ui/LoaderDots';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import { refundId, amount, createdAt } from 'rzp/ui/item/pair';

import ShowWhen from 'merchant/components/ShowWhen';

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

const RefundsList = ({ refunds }) => {
  const refundsHeading = {
    title: 'Refund Details',
    subTitle: <NumRefunds refunds={refunds} titleCase={true} />,
  };

  return (
    <ContentToggler>
      <span>Refund Details</span>
      <div className="full-width-item sub-entity-list">
        <DataTable
          customClass="refunds-table"
          progressLoader={true}
          title="Refunds"
          columns={[refundId, amount, createdAtWithStyle]}
          items={refunds.items}
          loading={refunds.loading}
          showHeaders={false}
          noStripe={true}
          panelHeading={refundsHeading}
        />
      </div>
    </ContentToggler>
  );
};

export default ({ payment, refunds, openRefundModal }) => {
  const paymentStatus = payment.status,
    refundStatus = payment.refund_status,
    refundAmount = payment.amount_refunded;

  if (['created', 'authorized', 'failed'].indexOf(paymentStatus) >= 0) {
    return (
      <Definition>
        <span>Not Applicable</span>
        <span>Only captured payments can be refunded.</span>
      </Definition>
    );
  } else if (paymentStatus === 'captured') {
    return (
      <div>
        <div className="m-b">
          {refundStatus === 'partial'
            ? <Definition>
                <span>
                  <Amount value={refundAmount} /> Refunded
                </span>
                <span>
                  Partially refunded in <NumRefunds refunds={refunds} />
                </span>
              </Definition>
            : <Definition>No refunds issued yet</Definition>}
        </div>
        {
          <ShowWhen myRole="owner manager operations admin">
            <p>
              <button className="btn btn-default" onClick={openRefundModal}>
                {`Issue${refundStatus === 'partial' ? ' another' : ''} Refund`}
              </button>
            </p>
          </ShowWhen>
        }
        {refundStatus === 'partial' && <RefundsList refunds={refunds} />}
      </div>
    );
  } else if (paymentStatus === 'refunded') {
    if (!refundStatus) {
      // Un captured refunds will be auto refunded
      return (
        <Definition>
          <span>Auto Refunded</span>
          <span>
            Payment was not captured within 5 days of creation, hence it was
            automatically refunded.
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
          {<RefundsList refunds={refunds} />}
        </div>
      );
    }
  }

  return null;
};
