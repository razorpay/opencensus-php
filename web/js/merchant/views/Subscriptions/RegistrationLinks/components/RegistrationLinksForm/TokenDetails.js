import moment from 'moment';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { getFormattedAmount, rupeesToPaise, currencySymbols } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';

import { AmountTooltip } from 'common/ui/Amount';

import { checkIfAmount, checkIfAmountForFirstCharge } from './PaymentDetails/utils';
import {
  MAX_TOKEN_AMOUNT,
  CARD_AFA_MAX_LIMIT,
  CARD_TOKEN_MAX_AMOUNT,
  MAX_TOKEN_AMOUNT_NACH,
} from 'merchant/views/Subscriptions/constants';

const OptionLabel = ({ title, desc }) => (
  <div className="label-container">
    <span className="token-radio-label">{title}</span>
    <div className="text-fade">{desc}</div>
  </div>
);

const BILLING_FREQUENCY_OPTIONS = [
  {
    label: () => <OptionLabel title="Monthly" desc="You can charge the customer once in a month" />,
    value: 'monthly',
  },
  {
    label: () => (
      <OptionLabel title="As and When Presented" desc="You can charge the customer any time" />
    ),
    value: 'as_presented',
  },
];
const maxAmountValidator = (methodAmount, maxAmount) => (value) => {
  const isAmountCheckFiled = checkIfAmount(value);

  if (isAmountCheckFiled) {
    return isAmountCheckFiled;
  }

  const amount = rupeesToPaise(Number(value));

  if (amount > maxAmount) {
    return `Max amount should not be greater than ${getFormattedAmount(maxAmount)}`;
  } else if (amount < rupeesToPaise(Number(methodAmount))) {
    return 'Max amount should be greater than amount set for this payment method';
  }
  return null;
};

const cardMaxAmountValidator = (maxAllowedAmount, currencySym) => (value) => {
  if (value > maxAllowedAmount) {
    return `Please enter an amount below ${currencySym}${maxAllowedAmount}`;
  }
  return null;
};

export default function TokenDetailsForm({
  amount,
  frequency,
  isNACHPayment,
  isUPIPayment,
  isCardPayment,
  isFirstAmountHidden,
  defaultMandateMaxAmount,
  defaultFirstChargeAmount,
  tokenHasNoExpiry,
  handleDateChange,
  mandateMaxAmount,
  firstPaymentAmount,
  mandateExpireAt,
  onBlurElement,
  currency,
}) {
  const maxAmountProps = {
    validator: maxAmountValidator(amount, MAX_TOKEN_AMOUNT),
    description: `Max Amount for Mandate (Up to ${getFormattedAmount(MAX_TOKEN_AMOUNT)})`,
  };
  const currencySym = currencySymbols[currency];

  if (isUPIPayment) {
    maxAmountProps.placeholder = `Max ${getFormattedAmount(UPI_AVL_LIMIT)}`;
    maxAmountProps.validator = maxAmountValidator(amount, UPI_AVL_LIMIT);
    maxAmountProps.description =
      'This is the maximum you can charge the customer per billing cycle';
  }

  if (isNACHPayment) {
    maxAmountProps.validator = maxAmountValidator(amount, MAX_TOKEN_AMOUNT_NACH);
    maxAmountProps.description = `Max Amount for Nach (Up to ${getFormattedAmount(
      MAX_TOKEN_AMOUNT_NACH,
    )})`;
  }
  if (isCardPayment) {
    maxAmountProps.validator = cardMaxAmountValidator(CARD_TOKEN_MAX_AMOUNT, currencySym);
    let maxAmount = CARD_AFA_MAX_LIMIT;
    if (mandateMaxAmount <= CARD_AFA_MAX_LIMIT) {
      maxAmount = mandateMaxAmount;
    }
    maxAmountProps.description = () => (
      <>
        You can <strong>automatically</strong> charge the customer upto {currencySym}
        {maxAmount} for each recurring payment. Payments above {currencySym}
        {maxAmount} will ask for OTP verification from the customer.
      </>
    );
  }

  return (
    <>
      {isUPIPayment && (
        <div class="Input billing-frequency ">
          <Input.Group label="Billing Frequency" class="InputGroup--vTop">
            <Input.Radio
              class="Input--vTop"
              name="frequency"
              data-name="billing_frequency"
              options={BILLING_FREQUENCY_OPTIONS}
              defaultValue={frequency}
            />
          </Input.Group>
        </div>
      )}
      {!isCardPayment && (
        <>
          <Input.Group label="Expiry of Token" class="InputGroup--vTop">
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
          {/* TODO: Q3 Input.Amount */}
          <Input
            type="number"
            name="mandateMaxAmount"
            placeholder={defaultMandateMaxAmount}
            label="Maximum Billing Amount"
            data-name="token_max_amount"
            onBlur={onBlurElement}
            addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
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
              label="First Charge Amount"
              class="Input--Amount"
              description="Amount of First Charge"
              data-name="first_payment_amount"
              onBlur={onBlurElement}
              value={firstPaymentAmount}
              // TODO: validators need to re-run if the sibling element(here mandateMaxAmount) is changed
              validator={firstPaymentAmountValidator(mandateMaxAmount)}
              addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
            />
          )}
        </>
      )}
      {isCardPayment && (
        <Input.Group label="Expiry of Token" class="InputGroup--vTop">
          <Input.Check
            fieldLabel="Same as expiry of customer’s card"
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
            onChange={handleDateChange('mandateExpireAt')}
            disabled={!!Number(tokenHasNoExpiry)}
            data-name="token_expiry_date"
            onBlur={onBlurElement}
            defaultValue={mandateExpireAt ? moment(mandateExpireAt, 'X') : null}
          />
          {!tokenHasNoExpiry && (
            <div class="Input Input--half_big disable-past-year Input--Calendar">
              <div class="Input-content Input-desc">
                If the chosen date is beyond the expiry date of the customer’s card, then it will be
                reset to the card expiry date
              </div>
            </div>
          )}
        </Input.Group>
      )}
      {isCardPayment && (
        <Input
          type="number"
          size="big"
          class="Input--Amount"
          name="mandateMaxAmount"
          data-name="token_max_amount"
          label={() => (
            <>
              Maximum Auto-debit Amount
              <div className="Input-desc sub-text">(For domestic cards only)</div>
            </>
          )}
          placeholder={`Max ${CARD_AFA_MAX_LIMIT}`}
          onBlur={onBlurElement}
          required={isUPIPayment}
          value={mandateMaxAmount}
          validator={checkIfAmount}
          addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
          {...maxAmountProps}
        />
      )}
    </>
  );
}

function firstPaymentAmountValidator(mandateMaxAmount) {
  return (value) => checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}
