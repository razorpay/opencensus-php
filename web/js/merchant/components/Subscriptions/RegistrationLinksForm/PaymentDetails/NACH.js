import Input from 'component/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default ({
  accountType,
  nachBanks,
  isNachFormAval,
  bankAccountIFSC,
  bankName,
  beneficiaryName,
  bankAccountNumber,
}) => (
  <React.Fragment>
    <Input.Check
      name="isNachFormAval"
      label="NACH Form"
      class="InputGroup--vTop"
      checked={isNachFormAval}
      fieldLabel="I have Customer's signed form"
    />

    <BankDetails
      required
      options={nachBanks}
      bankName={bankName}
      bankAccountIFSC={bankAccountIFSC}
    />

    <AccountDetails
      required
      beneficiaryName={beneficiaryName}
      bankAccountNumber={bankAccountNumber}
    >
      <Input.Select
        name="accountType"
        options={['Select Bank', ...OPTIONS]}
        placeholder="Account Type"
        value={accountType}
      />
    </AccountDetails>
  </React.Fragment>
);

const OPTIONS = [
  {
    label: 'Savings Bank',
    name: 'savings',
  },
  {
    label: 'Current Bank',
    name: 'current',
  },
];
