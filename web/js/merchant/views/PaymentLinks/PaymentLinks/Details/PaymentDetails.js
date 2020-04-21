import { Link } from 'react-router-dom';
import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import DataTable from 'common/ui/Table/DataTable';
import { paymentId, amount, paidOn } from 'common/ui/item/pair';
import Time from 'common/ui/Time';

const PaymentDetails = ({ paymentlink, isPaymentlinksV2Enabled }) => {
  let hasPaymentDetails = false;

  if (isPaymentlinksV2Enabled) {
    hasPaymentDetails =
      paymentlink.partial_payment &&
      paymentlink.payments &&
      paymentlink.payments.length;
  } else {
    hasPaymentDetails =
      paymentlink.partial_payment &&
      paymentlink.payments &&
      paymentlink.payments.items &&
      paymentlink.payments.items.length;
  }

  return (
    <Definition placeholder="--">
      <Amount value={paymentlink.amount_paid} currency={paymentlink.currency} />
      {hasPaymentDetails ? (
        <ContentToggler>
          <span>View Payment Details</span>
          <div
            className="full-width-item sub-entity-list"
            style={{ fontSize: 14 }}
          >
            <DataTable
              title="Payments"
              progressLoader={true}
              columns={[paymentId, paidOn, amount]}
              items={
                isPaymentlinksV2Enabled
                  ? paymentlink.payments
                  : paymentlink.payments.items
              }
              noStripe={true}
            />
          </div>
        </ContentToggler>
      ) : (
        <React.Fragment>
          {paymentlink.payment_id && (
            <Link to={`/payments/${paymentlink.payment_id}`}>
              <code>{paymentlink.payment_id}</code>
            </Link>
          )}
          {paymentlink.paid_at && (
            <div>
              Paid on{' '}
              <Time value={paymentlink.paid_at} format="DD MMM YYYY, hh:mm a" />
            </div>
          )}
        </React.Fragment>
      )}
    </Definition>
  );
};

export default PaymentDetails;
