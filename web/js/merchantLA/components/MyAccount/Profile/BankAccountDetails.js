import DetailRow from 'merchant/components/DetailRow';

export default ({ bankAccount }) => {
  return (
    <div class="panel panel-default">
      <div class="panel-heading">Bank Account</div>
      <div class="list-group details-row-container">
        <DetailRow label="IFSC Code" value={bankAccount.ifsc} />
        <DetailRow label="Account Number" value={bankAccount.account_number} />
        <DetailRow label="Beneficiary" value={bankAccount.name} />
      </div>
    </div>
  );
};
