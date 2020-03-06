import EntityDetailRow from 'merchant/components/EntityDetailRow';
import { classList } from 'common/utils/rzp-utils';

export default function VirtualAccountDetails({
  bankAccount1,
  bankAccount2,
  upiAddress,
}) {
  return (
    <React.Fragment>
      {bankAccount1 && (
        <div
          class={
            classList(isYESBankAccountVA(bankAccount1)) &&
            'VirtualAccountDetails-group--disabled'
          }
        >
          <EntityDetailRow label="Account Number">
            <b>{bankAccount1.account_number}</b>
          </EntityDetailRow>

          <EntityDetailRow label="Beneficiary Name">
            <b>{bankAccount1.name}</b>
          </EntityDetailRow>

          <EntityDetailRow label="IFSC Code">
            <b>{bankAccount1.ifsc}</b>
          </EntityDetailRow>
        </div>
      )}

      {bankAccount2 && (
        <div
          class={
            classList(isYESBankAccountVA(bankAccount2)) &&
            'VirtualAccountDetails-group--disabled'
          }
        >
          <EntityDetailRow label="Account Number">
            <b>{bankAccount2.account_number}</b>
          </EntityDetailRow>

          <EntityDetailRow label="Beneficiary Name">
            <b>{bankAccount2.name}</b>
          </EntityDetailRow>

          <EntityDetailRow label="IFSC Code">
            <b>{bankAccount2.ifsc}</b>
          </EntityDetailRow>
        </div>
      )}

      {upiAddress && (
        <EntityDetailRow label="UPI Address">
          <b>{upiAddress.address}</b>
        </EntityDetailRow>
      )}
    </React.Fragment>
  );
}

/*
* Separates bankAccount and upiAddress
* */
export function getVirtualAccountDetailsToCopy({
  bankAccount1,
  bankAccount2,
  upiAddress,
}) {
  let valueToCopy = [];

  // If both bankAccount1 and bankAccount2 exists, then one of them must be YES Bank. Don't add this one in Clipboard.

  if (bankAccount1 && !isYESBankAccountVA(bankAccount1)) {
    const bankAccountDetails = `Account Number: ${
      bankAccount1.account_number
    }\nBeneficiary Name: ${bankAccount1.name}\nIFSC: ${bankAccount1.ifsc}`;
    valueToCopy.push(bankAccountDetails);
  }

  if (bankAccount2 && !isYESBankAccountVA(bankAccount2)) {
    const bankAccountDetails = `Account Number: ${
      bankAccount2.account_number
    }\nBeneficiary Name: ${bankAccount2.name}\nIFSC: ${bankAccount2.ifsc}`;
    valueToCopy.push(bankAccountDetails);
  }

  if (upiAddress) {
    const upiAddressDetails = `UPI Address: ${upiAddress.address}`;
    valueToCopy.push(upiAddressDetails);
  }

  valueToCopy = valueToCopy.join('\n');

  return valueToCopy;
}

/*
* Separates bankAccount and upiAddress
* */
export function getVirtualAccountDetails(virtualaccount) {
  let bankAccount1, bankAccount2, upiAddress;

  virtualaccount.receivers &&
    virtualaccount.receivers.length &&
    virtualaccount.receivers.forEach(vaItem => {
      if (vaItem.entity === 'bank_account') {
        if (!bankAccount1) {
          bankAccount1 = vaItem;
        } else {
          bankAccount2 = vaItem;
        }
      } else {
        upiAddress = vaItem;
      }
    });

  return { bankAccount1, bankAccount2, upiAddress };
}

export function isYESBankAccountVA(bankAccount) {
  const ifsc = bankAccount && bankAccount.ifsc.toLowerCase();

  if (ifsc && ifsc.toLowerCase().indexOf('yes') > -1) {
    return true;
  }

  return false;
}
