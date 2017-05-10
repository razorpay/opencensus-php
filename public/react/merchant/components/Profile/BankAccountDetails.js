import DetailRow from '../DetailRow';

export default ({ bankAccount }) => {
  return (
    <div class="panel-detail-container">
      <div class="panel-heading">
        Bank Account
      </div>
      <div class="panel panel-default">
        <div class="list-group">
          <DetailRow label="IFSC Code" value={bankAccount.ifsc_code} />
          <DetailRow
            label="Account Number"
            value={bankAccount.account_number}
          />
          <DetailRow label="Beneficiary" value={bankAccount.beneficiary_name} />
        </div>
      </div>
    </div>
  );
};
