import Input from 'common/new-ui/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  emandateBanks,
  skipBankDetails,
  bankAccountIFSC,
  bankName,
  accountType,
  beneficiaryName,
  bankAccountNumber,
  trackSkipBankDetails,
}) => (
  <React.Fragment>
    <Input.Check
      name="skipBankDetails"
      checked={skipBankDetails}
      defaultChecked="0"
      fieldLabel="Skip Bank Details"
      onChange={trackSkipBankDetails}
    />

    <BankDetails
      required={!skipBankDetails}
      disabled={skipBankDetails}
      options={emandateBanks}
      bankName={bankName}
      bankAccountIFSC={bankAccountIFSC}
    />

    <AccountDetails
      required={!skipBankDetails}
      disabled={skipBankDetails}
      accountType={accountType}
      beneficiaryName={beneficiaryName}
      bankAccountNumber={bankAccountNumber}
    />
  </React.Fragment>
);
