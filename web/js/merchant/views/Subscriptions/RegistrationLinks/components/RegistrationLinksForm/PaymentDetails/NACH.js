import Input from 'common/new-ui/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  formReference1,
  formReference2,
  accountType,
  bankAccountIFSC,
  beneficiaryName,
  bankAccountNumber,
}) => (
  <React.Fragment>
    <BankDetails required hideBankName bankAccountIFSC={bankAccountIFSC} />

    <AccountDetails
      required
      beneficiaryName={beneficiaryName}
      bankAccountNumber={bankAccountNumber}
      accountType={accountType}
    />

    <Input name="formReference1" value={formReference1} label="Reference 1" />

    <Input name="formReference2" value={formReference2} label="Reference 2" />
  </React.Fragment>
);
