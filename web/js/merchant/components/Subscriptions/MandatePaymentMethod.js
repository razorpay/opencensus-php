import { titleCase } from 'rzp/utils/rzp-utils';

import Definition from 'rzp/ui/Definition';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';

export default function MandatePaymentMethod({ mandate }) {
  const { method, bank_account, card, bank: issuer } = mandate;
  if (method === 'emandate') {
    return (
      <Definition>
        <strong>
          {bank_account &&
            bank_account.bank_name &&
            bank_account.bank_name + ' - '}
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
            Max Amount: <Amount value={mandate.max_amount} />{' '}
          </>
        )}
      </Definition>
    );
  }

  if (method === 'card') {
    return !!card ? (
      <Definition>
        <strong>Card</strong>
        <>
          {!!card.issuer && card.issuer + ', '}
          {card.network} ending in {card.last4}}
        </>
        <>Name on card - {card.name}</>
      </Definition>
    ) : (
      'Card'
    );
  }

  return '--';
}
