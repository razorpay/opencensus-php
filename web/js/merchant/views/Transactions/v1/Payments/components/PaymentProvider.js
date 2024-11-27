import ContentToggler from 'common/ui/Toggler/ContentToggler';
import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import { Box } from '@razorpay/blade/components';

export default ({ payment, isTransactionV2DetailsView }) => {
  const paymentProvider = payment.provider;
  let el = null;

  if (paymentProvider === 'cred') {
    const paymentInfo = (
      <Box display="flex" flexDirection="column">
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
      </Box>
    );

    el = isTransactionV2DetailsView ? (
      <>
        <span>{paymentProvider.toUpperCase()}</span>
        {paymentInfo}
      </>
    ) : (
      <ContentToggler>
        <span>{paymentProvider.toUpperCase()}</span>
        <Definition allowEmptyTitle={true}>
          {null}
          {paymentInfo}
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
