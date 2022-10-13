import React from 'react';
import Input from 'common/new-ui/Input';

import AmountScreen from './Amount';
import { BankDetails, AccountDetails } from './commonFields';

export default function UPI({
  amount,
  showTPV,
  handleTPV,
  accountType,
  isTPVEnabled,
  onBlurElement,
  beneficiaryName,
  amountValidator,
  bankAccountIFSC,
  bankAccountNumber,
  isTPVEnabledMerchant,
}) {
  return (
    <>
      <AmountScreen
        amount={amount}
        onBlurElement={onBlurElement}
        placeholder="Max 200000"
        amountValidator={amountValidator}
      />

      {showTPV && !isTPVEnabledMerchant && (
        <>
          <Input.Check
            fieldLabel="Enable Third Party Validation"
            class="Input--vTop"
            onChange={handleTPV}
            checked={isTPVEnabled}
          />

          <BankDetails hideBankName disabled={!isTPVEnabled} bankAccountIFSC={bankAccountIFSC} />

          <Input
            disabled={!isTPVEnabled}
            placeholder="Account Number"
            name="bankAccountNumber"
            data-name="account_number"
            value={bankAccountNumber}
            description="Bank Account Number"
          />
        </>
      )}

      {/* IF Tpv feature is enabled, All UPI orders should have bank details associated with them */}
      {isTPVEnabledMerchant && (
        <>
          <BankDetails hideBankName required bankAccountIFSC={bankAccountIFSC} />
          <AccountDetails
            accountType={accountType}
            beneficiaryName={beneficiaryName}
            bankAccountNumber={bankAccountNumber}
            onBlurElement={onBlurElement}
            required
          />
        </>
      )}
    </>
  );
}
