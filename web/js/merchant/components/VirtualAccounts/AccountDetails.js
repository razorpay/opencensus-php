import CustomClipboard from 'rzp/ui/Clipboard/Custom';
import EntityDetailRow from 'merchant/components/EntityDetailRow';

const VirtualAccountDetails = ({ virtualaccount, onCopy }) => {
  let bankAccount, upiAddress;

  virtualaccount.receivers &&
    virtualaccount.receivers.forEach(vaItem => {
      if (vaItem.entity === 'bank_account') {
        bankAccount = vaItem;
      } else {
        upiAddress = vaItem;
      }
    });

  let valueToCopy = [];

  if (bankAccount) {
    const bankAccountDetails = `Account Number: ${
      bankAccount.account_number
    }\nBeneficiary Name: ${virtualaccount.name}\nIFSC: ${bankAccount.ifsc}`;
    valueToCopy.push(bankAccountDetails);
  }

  if (upiAddress) {
    const upiAddressDetails = `UPI Id: ${upiAddress.id}`;
    valueToCopy.push(upiAddressDetails);
  }

  valueToCopy = valueToCopy.join('\n');

  return (
    <div class="VirtualAccountDetails">
      <EntityDetailRow label="Account Details">
        <CustomClipboard
          value={valueToCopy}
          onCopy={() => {
            onCopy(virtualaccount);
          }}
        >
          <div class="copy btn btn-link no-padding">Copy Details</div>
        </CustomClipboard>
      </EntityDetailRow>

      <div class="divider" />

      {bankAccount && (
        <React.Fragment>
          <EntityDetailRow label="Account Number">
            <b>{bankAccount.account_number}</b>
          </EntityDetailRow>

          <EntityDetailRow label="Beneficiary Name">
            <b>{virtualaccount.name}</b>
          </EntityDetailRow>

          <EntityDetailRow label="IFSC Code">
            <b>{bankAccount.ifsc}</b>
          </EntityDetailRow>
        </React.Fragment>
      )}

      {upiAddress && (
        <EntityDetailRow label="UPI Id">
          <b>{upiAddress.id}</b>
        </EntityDetailRow>
      )}
    </div>
  );
};

export default VirtualAccountDetails;
