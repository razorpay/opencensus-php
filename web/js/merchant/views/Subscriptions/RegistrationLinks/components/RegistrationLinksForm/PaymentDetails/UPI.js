import React from 'react';
import Input from 'common/new-ui/Input';

import AmountScreen from './Amount';
import { BankDetails } from './commonFields';

export default function UPI(props) {
  return (
    <>
      <AmountScreen
        amount={props.amount}
        onBlurElement={props.onBlurElement}
        placeholder="Max 200000"
        amountValidator={props.amountValidator}
      />

      {props.showTPV && (
        <>
          <Input.Check
            fieldLabel="Enable Third Party Validation"
            class="Input--vTop"
            onChange={props.handleTPV}
            checked={props.isTPVEnabled}
          />

          <BankDetails
            hideBankName
            disabled={!props.isTPVEnabled}
            bankAccountIFSC={props.bankAccountIFSC}
          />

          <Input
            disabled={!props.isTPVEnabled}
            placeholder="Account Number"
            name="bankAccountNumber"
            data-name="account_number"
            value={props.bankAccountNumber}
            description="Bank Account Number"
          />
        </>
      )}
    </>
  );
}
