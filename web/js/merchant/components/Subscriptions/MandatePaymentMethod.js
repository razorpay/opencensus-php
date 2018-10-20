import { titleCase } from 'rzp/utils/rzp-utils';

import Definition from 'rzp/ui/Definition';
import Amount from 'rzp/ui/Amount';
import Time from 'rzp/ui/Time';

export default function MandatePaymentMethod({ mandate }) {
  const { method, bank_account } = mandate;
  return method === 'emandate' ? (
    <Definition>
      <strong>{bank_account && bank_account.bank_name + '- '} Emandate</strong>
      {/* token expiry of mandate */}
      {mandate.expire_at && (
        <>
          Token Expiry: <Time value={mandate.expire_at} />{' '}
        </>
      )}

      {/* max amount of mandate */}
      {!!mandate.max_amount && (
        <>
          Max Amount: <Amount value={mandate.max_amount} />{' '}
        </>
      )}
      {mandate.auth_type && <>Auth Type: {titleCase(mandate.auth_type)} </>}
    </Definition>
  ) : (
    'Card'
  );
}
