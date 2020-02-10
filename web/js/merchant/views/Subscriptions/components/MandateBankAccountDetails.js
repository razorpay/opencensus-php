import Definition from 'common/ui/Definition';
import { capitalize } from 'common/utils/rzp-utils';

export default function MandateBankAccountDetails({ bankDetails = {}, bank }) {
  return (
    <Definition>
      <strong>{bank}</strong>

      {/* bank account number */}
      <>Account Number: {bankDetails.account_number}</>

      {/* name on account */}
      <>Beneficiary Name: {bankDetails.beneficiary_name}</>

      {/* Type of Account */}
      <>Account Type: {capitalize(bankDetails.account_type)}</>

      {/* IFSC */}
      <>IFSC: {bankDetails.ifsc}</>
    </Definition>
  );
}
