import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';

export default ({ payment }) => {
  const paymentProvider = payment.provider;
  let el = null;

  if (paymentProvider === 'cred') {
    el = (
      <ContentToggler>
        <span>{paymentProvider.toUpperCase()}</span>
        <Definition allowEmptyTitle={true}>
          {null}
          <span>
            Paid via Card:
            <Amount
              value={payment.acquirer_data.amount * 100}
              currency={payment.currency}
              className="cred-payment-amount"
            />
          </span>
          <span>
            Paid via Cred Coins:
            <Amount
              value={payment.acquirer_data.discount * 100}
              currency={payment.currency}
              className="cred-payment-amount"
            />
          </span>
        </Definition>
      </ContentToggler>
    );
  } else if (paymentProvider === 'google_pay') {
    el = (
      <Definition>
        <span>Google Pay</span>
      </Definition>
    );
  } else {
    el = (
      <Definition>
        <span>{paymentProvider}</span>
      </Definition>
    );
  }

  return el;
};
