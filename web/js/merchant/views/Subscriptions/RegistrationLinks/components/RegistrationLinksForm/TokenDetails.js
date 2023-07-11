import moment from 'moment';
import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import { getFormattedAmount, rupeesToPaise, currencySymbols } from 'common/utils/rzp-utils';
import Input from 'common/new-ui/Input';
import DocsLink from 'merchant/components/DocsLink';

import { AmountTooltip } from 'common/ui/Amount';

import { checkIfAmount, checkIfAmountForFirstCharge } from './PaymentDetails/utils';
import {
  MAX_TOKEN_AMOUNT,
  BILLING_FREQUENCY,
  FREQUENCY_DESC_MAP,
  CARD_AFA_MAX_AMOUNT,
  MAX_TOKEN_AMOUNT_NACH,
  CARD_MAX_AMOUNT_ALLOWED,
  RECURRING_TYPE,
  FREQUENCY,
  getDebitPatternDesc,
} from 'merchant/views/Subscriptions/constants';
import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

const CARD_PAYMENT_LABEL = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: (
    <>
      Maximum Auto-debit Amount
      <div className="Input-desc sub-text">(For domestic cards only)</div>
    </>
  ),
  [ORG_CUSTOM_CODE_MAP.CURLEC]: <>Maximum Auto-debit Amount</>,
};

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
  isValidRecurringValue,
  handleRecurringValueChange,
  mandateExpireAt,
  recurringValue,
  recurringType,
  onBlurElement,
  user,
  org,
}) {
  const maxAmountProps = {
    validator: maxAmountValidator(amount, MAX_TOKEN_AMOUNT),
    description: `Max Amount for Mandate (Up to ${getFormattedAmount(MAX_TOKEN_AMOUNT)})`,
  };
  const currency = user.merchant.currency;
  const isCardMultipleFrequencyEnabled = user?.isCardMultipleFrequencyEnabled;
  const isDebitPatternEnabled = user?.isDebitPatternEnabled;

  const countryCode = user.merchant.country_code;
  const customCode = org.custom_code;
  const currencySym = currencySymbols[currency];
  const cardPaymentLabelText =
    CARD_PAYMENT_LABEL[customCode] || CARD_PAYMENT_LABEL[ORG_CUSTOM_CODE_MAP.RAZORPAY];
  const cardAfaMaxLimit = CARD_AFA_MAX_AMOUNT[countryCode];
  const cardTokenMaxAmount = CARD_MAX_AMOUNT_ALLOWED[countryCode];

  let billingFrequency = BILLING_FREQUENCY;
  const isCardFrequencyEnabled = isCardPayment && isCardMultipleFrequencyEnabled;

  if (isUPIPayment) {
    if (!isDebitPatternEnabled) {
      billingFrequency = BILLING_FREQUENCY.filter(({ name }) => {
        return [FREQUENCY.MONTHLY, FREQUENCY.AS_PRESENTED].includes(name);
      });
    }
    maxAmountProps.placeholder = `Max ${getFormattedAmount(UPI_AVL_LIMIT)}`;
    maxAmountProps.description =
      'This is the maximum you can charge the customer per billing cycle';
    if (isDebitPatternEnabled) {
      maxAmountProps.required = true;
    }
  }

  if (isNACHPayment) {
    maxAmountProps.validator = maxAmountValidator(amount, MAX_TOKEN_AMOUNT_NACH);
    maxAmountProps.description = `Max Amount for Nach (Up to ${getFormattedAmount(
      MAX_TOKEN_AMOUNT_NACH,
    )})`;
  }
  if (isCardPayment) {
    billingFrequency = BILLING_FREQUENCY.filter(({ name }) => name !== FREQUENCY.QUARTERLY);
    maxAmountProps.validator = cardMaxAmountValidator(cardTokenMaxAmount, currencySym);
    let maxAmount = cardAfaMaxLimit;
    if (isCardMultipleFrequencyEnabled) {
      maxAmountProps.required = true;
    }
    if (mandateMaxAmount <= cardAfaMaxLimit) {
      maxAmount = mandateMaxAmount;
    }

    if (user.isOrgCurlec) {
      maxAmountProps.description = () => '';
    } else {
      maxAmountProps.description = () => (
        <>
          You can <strong>automatically</strong> charge the customer upto {currencySym}
          {maxAmount} for each recurring payment. Payments above {currencySym}
          {maxAmount} will ask for OTP verification from the customer.
        </>
      );
    }
  }

  return (
    <>
      {(isCardFrequencyEnabled || isUPIPayment) && (
        <Input.Select
          name="frequency"
          label=" Billing Frequency"
          className="Input--vTop Input--small"
          data-name="billing_frequency"
          options={billingFrequency}
          defaultValue={frequency}
          description={FREQUENCY_DESC_MAP[frequency]}
        />
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
          label={() => cardPaymentLabelText}
          placeholder={`Max ${cardAfaMaxLimit}`}
          onBlur={onBlurElement}
          value={mandateMaxAmount}
          addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
          {...maxAmountProps}
        />
      )}
      {isUPIPayment && isDebitPatternEnabled && (
        <Input.Group class="InputGroup--inline" label="Debit pattern (optional)">
          <div class="Input-content debit-pattern">
            <Input.Select
              class="Input--half_small"
              name="recurringType"
              data-name="recurring_type"
              options={RECURRING_TYPE}
              defaultValue={recurringType}
            />
            <Input
              class={`Input--half_small ${isValidRecurringValue ? 'is-invalid' : ''}`}
              name="recurringValue"
              data-name="recurring_value"
              type="number"
              value={recurringValue}
              onChange={handleRecurringValueChange}
              onBlur={onBlurElement}
            />
            <span
              className="Input-desc"
              style={{ color: `${isValidRecurringValue ? '#f05050' : ''}` }}
            >
              {getDebitPatternDesc(frequency === 'weekly' ? '1-7' : '1-31')}
            </span>
            <br />
            <DocsLink
              url="https://razorpay.com/docs/api/payments/recurring-payments/upi/create-authorization-transaction/#121-create-a-registration-link"
              title="Know more"
              style={{ padding: '4px 0px' }}
            />
          </div>
        </Input.Group>
      )}
    </>
  );
}

function firstPaymentAmountValidator(mandateMaxAmount) {
  return (value) => checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}
