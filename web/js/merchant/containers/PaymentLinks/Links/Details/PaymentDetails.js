import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import ContentToggler from 'common/ui/Toggler/ContentToggler';
import DataTable from 'common/ui/Table/DataTable';
import { paymentId, amount, paidOn } from 'common/ui/item/pair';

const PaymentDetails = ({ invoice }) => (
  <Definition placeholder="--">
    <Amount value={invoice.amount_paid} currency={invoice.currency} />
    {invoice.partial_payment &&
    invoice.payments &&
    invoice.payments.items.length ? (
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
            items={invoice.payments.items}
            noStripe={true}
          />
        </div>
      </ContentToggler>
    ) : (
      <React.Fragment>
        {invoice.payment_id && (
          <Link to={`/payments/${invoice.payment_id}`}>
            <code>{invoice.payment_id}</code>
          </Link>
        )}
        {invoice.paid_at && (
          <div>
            Paid on{' '}
            <Time value={invoice.paid_at} format="DD MMM YYYY, hh:mm a" />
          </div>
        )}
      </React.Fragment>
    )}
  </Definition>
);

export default PaymentDetails;
