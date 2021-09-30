import { titleCase } from 'common/utils/rzp-utils';

import Definition from 'common/ui/Definition';
import Amount from 'common/ui/Amount';
import Time from 'common/ui/Time';
import moment from 'moment';

const CARD_EXPIRY_DATE_FORMAT = 'MMM YYYY';
const CARD_EXPIRY_INPUTE_DATE_FORMAT = 'MM YYYY';
const CARD_MAX_AMOUNT = 500000;

const getCardExpiry = ({ expiry_month, expiry_year }) => {
  return moment(`${expiry_month} ${expiry_year}`, CARD_EXPIRY_INPUTE_DATE_FORMAT).format(
    CARD_EXPIRY_DATE_FORMAT,
  );
};

const BILLING_FREQUENCY = {
  monthly: 'Monthly',
  as_presented: 'As and When Presented',
};

export default function MandatePaymentMethod({ mandate }) {
  const { method, bank_account, card, bank: issuer } = mandate;
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
            Max Amount: <Amount value={mandate.max_amount} currency={'INR'} />{' '}
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
          <Amount value={mandate.max_amount || CARD_MAX_AMOUNT} currency={'INR'} />{' '}
        </>
      </Definition>
    );
  }

  if (method === 'upi') {
    return (
      <Definition>
        <strong>UPI</strong>
        <>{bank_account && bank_account.bank_name && bank_account.bank_name}</>
        <>Billing Frequency: {BILLING_FREQUENCY[mandate.frequency] || 'Monthly'} </>
        <>
          Max Billing Amount: <Amount value={mandate.max_amount} currency={'INR'} />
        </>
      </Definition>
    );
  }

  return titleCase(method);
}
