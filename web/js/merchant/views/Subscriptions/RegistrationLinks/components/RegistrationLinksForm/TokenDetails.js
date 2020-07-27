import { PowerSelect } from 'react-power-select';

import Input from 'common/new-ui/Input';

import { AmountTooltip } from 'common/ui/Amount';

import {
  checkIfAmount,
  checkIfAmountForFirstCharge,
} from './PaymentDetails/utils';
import { payment } from 'common/ui/item/id';

export default ({
  amount,
  isUPIPayment,
  isFirstAmountHidden,
  defaultMandateMaxAmount,
  defaultFirstChargeAmount,
  tokenHasNoExpiry,
  handleDateChange,
  mandateMaxAmount,
  firstPaymentAmount,
  mandateExpireAt,
  onBlurElement,
}) => {
  const maxAmountProps = {};

  if (isUPIPayment) {
    maxAmountProps.placeholder = 'Max 2000';
    maxAmountProps.validator = maxAmountValidator(amount);
  }

  return (
    <React.Fragment>
      {isUPIPayment && (
        <div class="Input payment-frequency">
          <div class="Input-label">Payment Frequency</div>
          <div class="Input-content">
            <div class="Input-elWrapper">
              <div class="Input-el"> Monthly </div>
            </div>
          </div>
        </div>
      )}

      <Input.Group label="Token Expiry" class="InputGroup--vTop">
        <Input.Check
          fieldLabel="Until cancelled"
          name="tokenHasNoExpiry"
          defaultValue="1"
          data-name="token_until_cancelled"
          onBlur={onBlurElement}
          checked={tokenHasNoExpiry}
        />

        <Input.ToCalendar
          disablePastDates
          name="mandateExpireAt"
          placeholder="Expiry (DD-MM-YYYY)"
          placement="topLeft"
          size="half_big"
          addonAfter={<i class="i i-date-range" />}
          description="Expiry of Token"
          onChange={handleDateChange('mandateExpireAt')}
          disabled={!!Number(tokenHasNoExpiry)}
          data-name="token_expiry_date"
          onBlur={onBlurElement}
          defaultValue={mandateExpireAt ? moment(mandateExpireAt, 'X') : null}
        />
      </Input.Group>

      <Input
        type="number"
        required={isUPIPayment}
        name="mandateMaxAmount"
        placeholder={defaultMandateMaxAmount}
        label="Token Max Amount"
        data-name="token_max_amount"
        onBlur={onBlurElement}
        addonBefore={
          <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
        }
        size="half_big"
        validator={checkIfAmount}
        description="Max Amount for Mandate"
        class="Input--Amount"
        value={mandateMaxAmount}
        {...maxAmountProps}
      />

      {!isFirstAmountHidden && !isUPIPayment && (
        <Input
          name="firstPaymentAmount"
          type="number"
          placeholder={defaultFirstChargeAmount}
          size="half_big"
          label="Amount"
          class="Input--Amount"
          description="Amount of First Charge"
          data-name="first_payment_amount"
          onBlur={onBlurElement}
          value={firstPaymentAmount}
          validator={firstPaymentAmountValidator(mandateMaxAmount)}
          addonBefore={
            <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
          }
        />
      )}
    </React.Fragment>
  );
};

function firstPaymentAmountValidator(mandateMaxAmount) {
  return value =>
    checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}

const maxAmountValidator = methodAMount => value => {
  const isAmountCheckFiled = checkIfAmount(value);

  if (isAmountCheckFiled) {
    return isAmountCheckFiled;
  }

  const amount = Number(value);

  if (amount > 2000) {
    return 'Max amount should not be greater than 2000';
  } else if (amount < methodAMount) {
    return 'Max amount should bet greater then amount set for this payment method';
  }
};
