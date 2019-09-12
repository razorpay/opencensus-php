import Input from 'component/Input';

import { AmountTooltip } from 'rzp/ui/Amount';

import {
  checkIfAmount,
  checkIfAmountForFirstCharge,
} from './PaymentDetails/utils';

export default ({
  defaultMandateMaxAMount,
  tokenHasNoExpiry,
  handleDateChange,
  mandateMaxAmount,
  firstPaymentAmount,
  mandateExpireAt,
}) => (
  <React.Fragment>
    <Input.Group label="Token Expiry" class="InputGroup--vTop">
      <Input.Check
        fieldLabel="Until cancelled"
        name="tokenHasNoExpiry"
        defaultValue="1"
        checked={tokenHasNoExpiry}
      />

      <Input.ToCalendar
        name="mandateExpireAt"
        placeholder="Expiry (DD-MM-YYYY)"
        disablePastDates
        placement="topLeft"
        size="half_big"
        addonAfter={<i class="i i-date-range" />}
        description="Expiry of Token"
        onChange={handleDateChange('mandateExpireAt')}
        value={mandateExpireAt}
        disabled={!!Number(tokenHasNoExpiry)}
      />
    </Input.Group>

    <Input
      name="firstPaymentAmount"
      type="number"
      placeholder="0"
      size="half_big"
      label="Amount"
      class="Input--Amount"
      description="Amount of First Charge"
      value={firstPaymentAmount}
      validator={value => {
        return checkIfAmountForFirstCharge(
          Number(mandateMaxAmount) || 100000,
          value
        );
      }}
      addonBefore={
        <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
      }
    />

    <Input
      name="mandateMaxAmount"
      placeholder={defaultMandateMaxAMount}
      label="Token Max Amount"
      addonBefore={
        <AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />
      }
      size="half_big"
      validator={checkIfAmount}
      description="Max Amount for Mandate"
      class="Input--Amount"
      value={mandateMaxAmount}
    />
  </React.Fragment>
);
