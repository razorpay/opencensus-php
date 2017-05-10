export default ({ bankAccount }) => {
  return (
    <div class="row wrapper">
      <div class="panel-heading m-t m-b">
        Bank Account
      </div>
      <div class="panel panel-default">
        <div class="list-group">
          <a class="list-group-item">
            <span class="pull-right">{bankAccount.ifsc_code}</span>
            IFSC Code
          </a>
          <a class="list-group-item">
            <span class="pull-right">{bankAccount.account_number}</span>
            Account Number
          </a>
          <a class="list-group-item">
            <span class="pull-right">
              {bankAccount.beneficiary_name}
            </span>
            Beneficiary
          </a>
        </div>
      </div>
    </div>
  );
};
