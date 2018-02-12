import CustomClipboard from 'rzp/ui/Clipboard/Custom';

export default ({ virtualaccount, onCopy }) => {
  let bankAccount = virtualaccount.receivers[0];
  return (
    <div class="row">
      <div class="col-sm-9">
        <table class="table table-bordered va-account-details">
          <tbody>
            <tr>
              <td class="text-muted">Account Number</td>
              <td>
                <b>{bankAccount.account_number}</b>
              </td>
            </tr>
            <tr>
              <td class="text-muted">Beneficiary Name</td>
              <td>
                <b>{virtualaccount.name}</b>
              </td>
            </tr>
            <tr>
              <td class="text-muted">IFSC Code</td>
              <td>
                <b>{bankAccount.ifsc}</b>
              </td>
            </tr>
            <tr>
              <td colSpan="2" class="text-center">
                <CustomClipboard
                  value={`Account Number: ${
                    bankAccount.account_number
                  }\nBeneficiary Name: ${virtualaccount.name}\nIFSC: ${
                    bankAccount.ifsc
                  }`}
                  onCopy={() => {
                    onCopy(virtualaccount);
                  }}
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
