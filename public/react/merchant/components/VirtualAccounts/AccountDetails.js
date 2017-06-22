import CustomClipboard from 'rzp/ui/Clipboard/Custom';

export default ({ virtualaccount }) => {
  return (
    <div class="row">
      <div class="col-sm-9">
        <table class="table table-bordered va-account-details">
          <tbody>
            <tr>
              <td class="text-muted">Account Number</td>
              <td>
                <b>{virtualaccount.bank_account.account_number}</b>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Beneficiary Name</td>
              <td><b>{virtualaccount.name}</b></td>
            </tr>
            <tr>
              <td class="text-muted">IFSC Code</td>
              <td><b>{virtualaccount.bank_account.ifsc}</b></td>
            </tr>
            <tr>
              <td colSpan="2" class="text-center">
                <CustomClipboard
                  value={`Account Number: ${virtualaccount.bank_account.account_number}\nBeneficiary Name: ${virtualaccount.name}\nIFSC: ${virtualaccount.bank_account.ifsc}`}
                >
                  <div class="copy">Copy to Clipboard</div>
                </CustomClipboard>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  );
};
