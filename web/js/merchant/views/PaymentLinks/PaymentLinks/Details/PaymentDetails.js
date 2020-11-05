import { Link } from 'react-router-dom';
import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import DataTable from 'common/ui/Table/DataTable';
import { paymentId as paymentIdCol, amount, paidOn } from 'common/ui/item/pair';
import Time from 'common/ui/Time';

const PaymentDetails = ({ paymentlink, isPaymentlinksV2Enabled }) => {
  let hasPartialPaymentDetails = false,
    paymentId,
    paidAt;

  if (isPaymentlinksV2Enabled) {
    hasPartialPaymentDetails =
      paymentlink.partial_payment && paymentlink.payments && paymentlink.payments.length;

    if (!hasPartialPaymentDetails && paymentlink.payments && paymentlink.payments.length) {
      paymentId = paymentlink.payments && paymentlink.payments[0].payment_id;
      paidAt = paymentlink.payments && paymentlink.payments[0].created_at;
    }
  } else {
    hasPartialPaymentDetails =
      paymentlink.partial_payment &&
      paymentlink.payments &&
      paymentlink.payments.items &&
      paymentlink.payments.items.length;

    if (!hasPartialPaymentDetails) {
      paymentId = paymentlink.payment_id;
      paidAt = paymentlink.paid_at;
    }
  }

  return (
    <Definition placeholder="--">
      <Amount value={paymentlink.amount_paid} currency={paymentlink.currency} />
      {hasPartialPaymentDetails ? (
        <ContentToggler>
          <span>View Payment Details</span>
          <div className="full-width-item sub-entity-list" style={{ fontSize: 14 }}>
            <DataTable
              title="Payments"
              progressLoader={true}
              columns={[paymentIdCol, paidOn, amount]}
              items={isPaymentlinksV2Enabled ? paymentlink.payments : paymentlink.payments.items}
              noStripe={true}
            />
          </div>
        </ContentToggler>
      ) : (
        <React.Fragment>
          {paymentId && (
            <Link to={`/payments/${paymentId}`}>
              <code>{paymentId}</code>
            </Link>
          )}
          {paidAt && (
            <div>
              Paid on <Time value={paidAt} format="DD MMM YYYY, hh:mm a" />
            </div>
          )}
        </React.Fragment>
      )}
    </Definition>
  );
};

export default PaymentDetails;
