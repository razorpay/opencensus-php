import Definition from 'rzp/ui/Definition';

export default function MandateBankAccountDetails({ bankDetails = {}, bank }) {
  return (
    <Definition>
      <strong>{bank}</strong>

      {/* bank account number */}
      <>Account Number: {bankDetails.account_number}</>

      {/* name on account */}
      <>Beneficiary Name: {bankDetails.beneficiary_name}</>

      {/* Type of Account */}
      <>Account Type: {bankDetails.account_type}</>

      {/* IFSC */}
      <>IFSC: {bankDetails.ifsc}</>
    </Definition>
  );
}
