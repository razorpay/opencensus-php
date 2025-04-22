import Input from 'common/new-ui/Input';

import { checkIfAmount } from './utils';
import { BankDetails, AccountDetails } from './commonFields';

export default function Emandate({
  amount,
  emandateBanks,
  skipBankDetails,
  bankAccountIFSC,
  bankName,
  accountType,
  beneficiaryName,
  bankAccountNumber,
  trackSkipBankDetails,
  onBlurElement,
}) {
  return (
    <>
      <Input.Check
        name="skipBankDetails"
        checked={skipBankDetails}
        defaultChecked="0"
        fieldLabel="Skip Bank Details"
        onChange={trackSkipBankDetails}
        onBlur={onBlurElement}
        data-name="skip_bank_details"
      />

      <BankDetails
        required={!skipBankDetails}
        disabled={skipBankDetails}
        options={emandateBanks}
        bankName={bankName}
        bankAccountIFSC={bankAccountIFSC}
        onBlurElement={onBlurElement}
      />

      <AccountDetails
        required={!skipBankDetails}
        disabled={skipBankDetails}
        accountType={accountType}
        beneficiaryName={beneficiaryName}
        bankAccountNumber={bankAccountNumber}
        onBlurElement={onBlurElement}
      />
    </>
  );
}
