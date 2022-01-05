import Input from 'common/new-ui/Input';

import { BankDetails, AccountDetails } from './commonFields';

export default function NACHForm({
  showNACHAccountTypes,
  formReference1,
  formReference2,
  accountType,
  bankAccountIFSC,
  beneficiaryName,
  bankAccountNumber,
  onBlurElement,
}) {
  return (
    <>
      <BankDetails
        required
        hideBankName
        bankAccountIFSC={bankAccountIFSC}
        onBlurElement={onBlurElement}
      />

      <AccountDetails
        required
        isNACHPayment={showNACHAccountTypes}
        beneficiaryName={beneficiaryName}
        bankAccountNumber={bankAccountNumber}
        accountType={accountType}
        onBlurElement={onBlurElement}
      />

      <Input
        name="formReference1"
        value={formReference1}
        label="Reference 1"
        data-name="form_reference1"
        onBlur={onBlurElement}
      />

      <Input
        name="formReference2"
        value={formReference2}
        label="Reference 2"
        data-name="form_reference2"
        onBlur={onBlurElement}
      />
    </>
  );
}
