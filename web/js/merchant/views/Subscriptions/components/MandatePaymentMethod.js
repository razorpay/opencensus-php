import { titleCase, rupeesToPaise } from 'common/utils/rzp-utils';
import { CARD_AFA_MAX_AMOUNT } from 'merchant/views/Subscriptions/constants';

import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import moment from 'moment';
const CARD_EXPIRY_DATE_FORMAT = 'MMM YYYY';
const CARD_EXPIRY_INPUTE_DATE_FORMAT = 'MM YYYY';

const getCardExpiry = ({ expiry_month, expiry_year }) => {
  return moment(`${expiry_month} ${expiry_year}`, CARD_EXPIRY_INPUTE_DATE_FORMAT).format(
    CARD_EXPIRY_DATE_FORMAT,
  );
};

const BILLING_FREQUENCY = {
  monthly: 'Monthly',
  as_presented: 'As and When Presented',
};

export default function MandatePaymentMethod({ mandate, user }) {
  const { method, bank_account, card, bank: issuer } = mandate;
  const countryCode = user.country_code;
  const cardAfaMaxLimit = CARD_AFA_MAX_AMOUNT[countryCode];
  if (method === 'emandate') {
    return (
      <Definition>
        <strong>
          {bank_account && bank_account.bank_name && `${bank_account.bank_name} - `}
          Emandate
        </strong>

        {/* bank name */}
        {issuer || null}

        {/* auth type of emandate */}
        {!!mandate.auth_type && <>Auth Type: {titleCase(mandate.auth_type)} </>}

        {/* token expiry of mandate */}
        {!!mandate.expired_at && (
          <>
            Token Expiry: <Time value={mandate.expired_at} />{' '}
          </>
        )}

        {/* max amount of mandate */}
        {!!mandate.max_amount && (
          <>
            Max Amount: <Amount value={mandate.max_amount} currency="INR" />{' '}
          </>
        )}
      </Definition>
    );
  }
  // TODO CAW
  if (method === 'card') {
    return card ? (
      <Definition>
        <strong>Card</strong>
        <>
          {!!card.issuer && `${card.issuer}, `}
          {card.network} ending in {card.last4}
        </>
        <>Name on card - {card.name}</>
        <>Card Expiry - {getCardExpiry(card)}</>
      </Definition>
    ) : (
      <Definition>
        <strong>Card</strong>
        <>
          Token Expiry:{' '}
          {mandate?.expire_at ? <Time value={mandate.expire_at} /> : 'Same as card expiry'}
        </>
        <>
          Max Auto-debit Amount:{' '}
          <Amount
            value={mandate.max_amount || rupeesToPaise(cardAfaMaxLimit)}
            currency={mandate.currency}
          />{' '}
        </>
      </Definition>
    );
  }

  if (method === 'upi') {
    let frequency = null;
    let maxAmount = null;
    if (mandate.subscription_registration) {
      frequency = mandate.subscription_registration?.frequency;
      maxAmount = mandate.subscription_registration?.max_amount;
    } else if (mandate.frequency) {
      frequency = mandate.frequency;
      maxAmount = mandate.max_amount;
    }
    return (
      <Definition>
        <strong>UPI</strong>
        {bank_account && bank_account.bank_name && bank_account.bank_name}
        {frequency && <>Billing Frequency: {BILLING_FREQUENCY[frequency]} </>}
        {maxAmount && (
          <>
            Max Billing Amount: <Amount value={maxAmount} currency="INR" />
          </>
        )}
      </Definition>
    );
  }

  return titleCase(method);
}
