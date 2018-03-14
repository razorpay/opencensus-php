import DetailRow from '../DetailRow';

export default ({
  bankAccount,
  onChangeBankAccountDetails,
  isBankAccountChangeAllowed,
}) => {
  return (
    <div class="panel panel-default">
      <div class="panel-heading">
        Bank Account
        {isBankAccountChangeAllowed && (
          <a class="pull-right" onClick={onChangeBankAccountDetails}>
            Request Change
          </a>
        )}
      </div>
      <div class="list-group details-row-container">
        <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow label="Beneficiary" value={bankAccount.name} />
      </div>
    </div>
  );
};
