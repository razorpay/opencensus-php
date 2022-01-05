import Input from 'common/new-ui/Input';

import { checkIfAmount } from './utils';
import { BankDetails, AccountDetails } from './commonFields';

export default function Emandate({
  showAmountField,
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
      {showAmountField && (
        <Input.Group class="InputGroup--inline" label="Amount">
          <div class="Input-content">
            <Input
              required
              name="amount"
              type="tel"
              placeholder="0.00"
              description="Amount of Registration Link Payment"
              value={amount}
              validator={checkIfAmount}
              size="half_big"
              class="Input--Amount"
              onBlur={onBlurElement}
              data-name="amount"
            />
          </div>
        </Input.Group>
      )}

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
