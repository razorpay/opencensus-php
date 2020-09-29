import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { getFormattedAmount, rupeesToPaise } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';

import { AmountTooltip } from 'common/ui/Amount';

import { checkIfAmount, checkIfAmountForFirstCharge } from './PaymentDetails/utils';

const MAX_TOKEN_AMOUNT = 100000000;

const MAX_TOKEN_AMOUNT_NACH = 1000000000;

export default ({
  amount,
  isNACHPayment,
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
  const maxAmountProps = {
    validator: maxAmountValidator(amount, MAX_TOKEN_AMOUNT),
    description: `Max Amount for Mandate (Up to ${getFormattedAmount(MAX_TOKEN_AMOUNT)})`,
  };

  if (isUPIPayment) {
    maxAmountProps.placeholder = `Max ${getFormattedAmount(UPI_AVL_LIMIT)}`;
    maxAmountProps.validator = maxAmountValidator(amount, UPI_AVL_LIMIT);
    maxAmountProps.description = `Max Amount for Mandate`;
  }

  if (isNACHPayment) {
    maxAmountProps.validator = maxAmountValidator(amount, MAX_TOKEN_AMOUNT_NACH);
    maxAmountProps.description = `Max Amount for Nach (Up to ${getFormattedAmount(
      MAX_TOKEN_AMOUNT_NACH,
    )})`;
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
        addonBefore={<AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />}
        size="half_big"
        validator={checkIfAmount}
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
          addonBefore={<AmountTooltip currency={'INR'} parentQuerySelector=".Modal" />}
        />
      )}
    </React.Fragment>
  );
};

function firstPaymentAmountValidator(mandateMaxAmount) {
  return (value) => checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}

const maxAmountValidator = (methodAmount, maxAmount) => (value) => {
  const isAmountCheckFiled = checkIfAmount(value);

  if (isAmountCheckFiled) {
    return isAmountCheckFiled;
  }

  const amount = rupeesToPaise(Number(value));

  if (amount > maxAmount) {
    return `Max amount should not be greater than ${getFormattedAmount(maxAmount)}`;
  } else if (amount < methodAmount) {
    return 'Max amount should bet greater then amount set for this payment method';
  }
};
