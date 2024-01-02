import React from 'react';

import AmountScreen from './Amount';
import { BankDetails } from './commonFields';
import Input from 'common/new-ui/Input';
import { validateBeneficiaryName } from 'common/utils/validators';

export default function UPI({
  amount,
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

      {/* IF Tpv feature is enabled, All UPI orders should have bank details associated with them */}
      {isTPVEnabledMerchant && (
        <>
          <BankDetails hideBankName required bankAccountIFSC={bankAccountIFSC} />
          <Input
            placeholder="Account Number"
            name="bankAccountNumber"
            data-name="account_number"
            value={bankAccountNumber}
            description="Bank Account Number"
            onBlur={onBlurElement}
          />
          <Input
            placeholder="Beneficiary Name"
            name="beneficiaryName"
            data-name="beneficiary_name"
            value={beneficiaryName}
            onBlur={onBlurElement}
            description="Customer/Beneficiary Name on the Account"
            validator={(value) =>
              !validateBeneficiaryName(value)
                ? 'Please enter a valid name as per your account'
                : null
            }
          />
        </>
      )}
    </>
  );
}
