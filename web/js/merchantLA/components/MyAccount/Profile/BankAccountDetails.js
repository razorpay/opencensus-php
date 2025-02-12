import DetailRow from 'merchant/components/DetailRow';

export default ({ bankAccount, isCountryIndia }) => {
  return (
    <div className="panel panel-default">
      <div className="panel-heading">Bank Account</div>
      <div className="list-group details-row-container">
        {isCountryIndia ? (
          <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        ) : (
          <DetailRow label="Bank Name" value={bankAccount.bank_name} />
        )}
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow label="Beneficiary" value={bankAccount.name} />
      </div>
    </div>
  );
};
