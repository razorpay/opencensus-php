import React from 'react';

import Amount from 'rzp/ui/Amount';
import ContentToggler from 'rzp/ui/Toggler/ContentToggler';
import Definition from 'rzp/ui/Definition';
import DataTable from 'rzp/ui/Table/DataTable';
import PlaceholderLoader from 'rzp/ui/PlaceholderLoader';
import { refundId, amount } from 'rzp/ui/item/pair';

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

const getRefundDetails = refunds => {
  return (
    <ContentToggler>
      <span>Refund Details</span>
      <div className="full-width-item">
        <DataTable
          customClass="refunds-table"
          progressLoader={true}
          title="Refunds"
          columns={[refundId, amount]}
          items={refunds.items}
          loading={refunds.loading}
          showHeaders={false}
        />
      </div>
    </ContentToggler>
  );
};

export default ({ payment, refunds, openRefundModal }) => {
  const paymentStatus = payment.status,
    refundStatus = payment.refund_status,
    refundAmount = payment.amount_refunded,
    numRefunds = refunds.items.length,
    refundSuffix = numRefunds > 1 ? 's' : '';

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
        {refundStatus === 'partial'
          ? <Definition>
              <span>
                <Amount value={refundAmount} /> Refunded
              </span>
              {!refunds.loading &&
                <span>
                  Partially refunded in {numRefunds + ' '}
                  refund{refundSuffix}
                </span>}
            </Definition>
          : <Definition>No refunds issued yet</Definition>}
        <p />
        {
          <ShowWhen myRole="owner manager operations admin">
            <p>
              <button className="btn btn-default" onClick={openRefundModal}>
                {`Issue${refundStatus === 'partial' ? ' another' : ''} Refund`}
              </button>
            </p>
          </ShowWhen>
        }
        {refunds.loading ? <PlaceholderLoader /> : getRefundDetails(refunds)}
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
        <Definition>
          <span>Fully Refunded</span>
          <span>
            Fully Refunded in {numRefunds} refund{refundSuffix}
          </span>
          {getRefundDetails(refunds)}
        </Definition>
      );
    }
  }

  return null;
};
