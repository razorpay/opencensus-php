import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';
import moment from 'moment';

import Input from 'common/new-ui/Input';
import { AmountTooltip } from 'common/ui/Amount';
import DocsLink from 'merchant/components/DocsLink';
import {
  FREQUENCY_DESC_MAP,
  CARD_AFA_MAX_AMOUNT,
  RECURRING_TYPE,
  FREQUENCY,
  PAYMENT_METHODS,
  DEFAULT_TOUCH_N_GO_MAX_LIMIT,
} from 'merchant/views/Subscriptions/constants';
import {
  getBillingFrequencies,
  getMaxAmountProps,
  getCardLabelText,
} from 'merchant/views/Subscriptions/helper';
import {
  getDebitPatternDesc,
  disablePastAndPostFortyYear,
} from 'merchant/views/Subscriptions/utils';

import { checkIfAmountForFirstCharge } from './PaymentDetails/utils';

export default function TokenDetailsForm({
  amount,
  method,
  frequency,
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
  const maxAmountProps = getMaxAmountProps(method, amount, user, mandateMaxAmount);
  const currency = user.merchant.currency;
  const isDebitPatternEnabled = user?.isDebitPatternEnabled;
  // Need to hide debit pattern fields if as_presented or daily frequency choosen
  const hideDebitPattern = ![FREQUENCY.AS_PRESENTED, FREQUENCY.DAILY].includes(frequency);

  const countryCode = user.merchant.country_code;
  const cardAfaMaxLimit = CARD_AFA_MAX_AMOUNT[countryCode];

  const billingFrequency = getBillingFrequencies(method);

  const renderTokenExpiryField = () => {
    if (method === PAYMENT_METHODS.EMANDATE || method === PAYMENT_METHODS.NACH) {
      return (
        <Input.Group label="Expiry of Token " class="InputGroup--vTop">
          <Input.ToCalendar
            data-testid="mandateExpireAt-date-input"
            disablePastDates
            name="mandateExpireAt"
            placeholder="Expiry (DD-MM-YYYY)"
            placement="topLeft"
            size="half_big"
            addonAfter={<i class="i i-date-range" />}
            description="Token expires in 40 years, unless otherwise specified."
            onChange={handleDateChange('mandateExpireAt')}
            data-name="token_expiry_date"
            onBlur={onBlurElement}
            defaultValue={
              mandateExpireAt ? moment(mandateExpireAt, 'X') : moment(moment().add(40, 'y'), 'X')
            }
            disabledDate={disablePastAndPostFortyYear}
          />
        </Input.Group>
      );
    }
    return (
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
    );
  };

  const renderMandateMaxAmountField = () => (
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
  );

  const renderFrequencyField = () => (
    <Input.Select
      name="frequency"
      label=" Billing Frequency"
      className="Input--vTop Input--small"
      data-name="billing_frequency"
      data-testid="billing_frequency"
      options={billingFrequency}
      defaultValue={frequency}
      description={FREQUENCY_DESC_MAP[frequency]}
    />
  );

  const renderDebitPatternField = () => (
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
        <span className="Input-desc" style={{ color: `${isValidRecurringValue ? '#f05050' : ''}` }}>
          {getDebitPatternDesc(frequency)}
        </span>
        <br />
        <DocsLink
          url="https://razorpay.com/docs/api/payments/recurring-payments/upi/create-authorization-transaction/#121-create-a-registration-link"
          title="Know more"
          style={{ padding: '4px 0px' }}
        />
      </div>
    </Input.Group>
  );

  const renderFirstChargeField = () => (
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
      validator={firstPaymentAmountValidator(mandateMaxAmount)}
      addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
    />
  );

  const renderTokenDetailsForm = () => {
    switch (method) {
      case PAYMENT_METHODS.WALLET:
        return (
          <>
            <Input.Group label="Expiry of Token" class="InputGroup--vTop">
              <Input.ToCalendar
                disablePastDates
                name="mandateExpireAt"
                placeholder="Expiry (DD-MM-YYYY)"
                placement="topLeft"
                size="half_big"
                addonAfter={<i class="i i-date-range" />}
                onChange={handleDateChange('mandateExpireAt')}
                data-name="token_expiry_date"
                onBlur={onBlurElement}
                required
                defaultValue={mandateExpireAt ? moment(mandateExpireAt, 'X') : null}
              />
            </Input.Group>
            <Input
              type="number"
              size="big"
              class="Input--Amount"
              name="mandateMaxAmount"
              data-name="token_max_amount"
              label="Maximum Auto-debit Amount"
              placeholder={`Max ${convertToMajorUnit(DEFAULT_TOUCH_N_GO_MAX_LIMIT, { currency })}`}
              onBlur={onBlurElement}
              value={mandateMaxAmount}
              addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
              {...maxAmountProps}
            />
          </>
        );
      case PAYMENT_METHODS.CARD:
        return (
          <>
            {renderFrequencyField()}
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
                    If the chosen date is beyond the expiry date of the customer’s card, then it
                    will be reset to the card expiry date
                  </div>
                </div>
              )}
            </Input.Group>
            <Input
              type="number"
              size="big"
              class="Input--Amount"
              name="mandateMaxAmount"
              data-name="token_max_amount"
              label={() => getCardLabelText(org.custom_code)}
              placeholder={`Max ${cardAfaMaxLimit}`}
              onBlur={onBlurElement}
              value={mandateMaxAmount}
              addonBefore={<AmountTooltip currency={currency} parentQuerySelector=".Modal" />}
              {...maxAmountProps}
            />
          </>
        );
      case PAYMENT_METHODS.UPI: {
        return (
          <>
            {renderFrequencyField()}
            {renderTokenExpiryField()}
            {renderMandateMaxAmountField()}
            {hideDebitPattern && isDebitPatternEnabled ? renderDebitPatternField() : null}
          </>
        );
      }
      case PAYMENT_METHODS.EMANDATE:
      case PAYMENT_METHODS.NACH: {
        return (
          <>
            {renderTokenExpiryField()}
            {renderMandateMaxAmountField()}
            {!isFirstAmountHidden ? renderFirstChargeField() : null}
          </>
        );
      }
      default:
        return 'Invalid method';
    }
  };

  return renderTokenDetailsForm();
}

function firstPaymentAmountValidator(mandateMaxAmount) {
  return (value) => checkIfAmountForFirstCharge(Number(mandateMaxAmount) || 100000, value);
}
