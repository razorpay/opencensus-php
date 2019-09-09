import Input from 'component/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  emandateBanks,
  skipBankDetails,
  bankAccountIFSC,
  bankName,
  beneficiaryName,
  bankAccountNumber,
}) => (
  <React.Fragment>
    <Input.Check
      name="skipBankDetails"
      checked={skipBankDetails}
      defaultChecked="0"
      fieldLabel="Skip Bank Details"
    />

    <BankDetails
      disabled={skipBankDetails}
      options={emandateBanks}
      bankName={bankName}
      bankAccountIFSC={bankAccountIFSC}
    />

    <AccountDetails
      disabled={skipBankDetails}
      beneficiaryName={beneficiaryName}
      bankAccountNumber={bankAccountNumber}
    />
  </React.Fragment>
);
