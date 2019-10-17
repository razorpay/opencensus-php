import EntityDetailRow from 'merchant/components/EntityDetailRow';

export default function VirtualAccountDetails({ bankAccount, upiAddress }) {
  return (
    <React.Fragment>
      {bankAccount && (
        <React.Fragment>
          <EntityDetailRow label="Account Number">
            <b>{bankAccount.account_number}</b>
          </EntityDetailRow>

          <EntityDetailRow label="Beneficiary Name">
            <b>{bankAccount.name}</b>
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
    </React.Fragment>
  );
}

/*
* Separates bankAccount and upiAddress
* */
export function getVirtualAccountDetailsToCopy({ bankAccount, upiAddress }) {
  let valueToCopy = [];

  if (bankAccount) {
    const bankAccountDetails = `Account Number: ${
      bankAccount.account_number
    }\nBeneficiary Name: ${bankAccount.name}\nIFSC: ${bankAccount.ifsc}`;
    valueToCopy.push(bankAccountDetails);
  }

  if (upiAddress) {
    const upiAddressDetails = `UPI Id: ${upiAddress.id}`;
    valueToCopy.push(upiAddressDetails);
  }

  valueToCopy = valueToCopy.join('\n');

  return valueToCopy;
}

/*
* Separates bankAccount and upiAddress
* */
export function getVirtualAccountDetails(virtualaccount) {
  let bankAccount, upiAddress;

  virtualaccount.receivers &&
    virtualaccount.receivers.length &&
    virtualaccount.receivers.forEach(vaItem => {
      if (vaItem.entity === 'bank_account') {
        bankAccount = vaItem;
      } else {
        upiAddress = vaItem;
      }
    });

  return { bankAccount, upiAddress };
}
