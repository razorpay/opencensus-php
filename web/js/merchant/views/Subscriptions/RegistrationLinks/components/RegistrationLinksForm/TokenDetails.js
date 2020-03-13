import Input from 'common/new-ui/Input';

import { AmountTooltip } from 'common/ui/Amount';

import {
  checkIfAmount,
  checkIfAmountForFirstCharge,
} from './PaymentDetails/utils';

export default ({
  isFirstAmountHidden,
  defaultMandateMaxAmount,
  defaultFirstChargeAmount,
  tokenHasNoExpiry,
  handleDateChange,
  mandateMaxAmount,
  firstPaymentAmount,
  mandateExpireAt,
  onBlurElement,
}) => (
  <React.Fragment>
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
        disabled={!!Number(tokenHasNoExpiry)}
      />
    </Input.Group>

    <Input
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
    />

    {!isFirstAmountHidden && (
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

function firstPaymentAmountValidator(mandateMaxAmount) {
  return value =>
    checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}
