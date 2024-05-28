import { Amount } from '@razorpay/blade/components';
import { convertToMajorUnit } from '@razorpay/i18nify-js/currency';

import { UPI_AVL_LIMIT } from 'merchant/helpers/data';
import {
  CARD_AFA_MAX_LIMIT,
  CARD_TOKEN_MAX_AMOUNT,
  MY_CARD_MAX_AMOUNT,
  FREQUENCY,
  BILLING_FREQUENCY,
  CARD_FREQUENCY,
  PAYMENT_METHODS,
  MAX_TOKEN_AMOUNT,
  MAX_TOKEN_AMOUNT_NACH,
  DEFAULT_TOUCH_N_GO_MAX_LIMIT,
} from 'merchant/views/Subscriptions/constants';
import {
  getCardLabelAndLimits,
  getCardLabelText,
  shouldHideDebitPattern,
  getBillingFrequencies,
  maxAmountValidator,
  cardMaxAmountValidator,
  getMaxAmountProps,
} from 'merchant/views/Subscriptions/helper';
import { render } from 'test-utils';

const maxAmountRenderer = (func) => render(func);

test('getCardLabelAndLimits should return expected limits', () => {
  expect(getCardLabelAndLimits('IN').cardAfaMaxLimit).toEqual(CARD_AFA_MAX_LIMIT);
  expect(getCardLabelAndLimits('IN').cardTokenMaxAmount).toEqual(CARD_TOKEN_MAX_AMOUNT);

  expect(getCardLabelAndLimits('MY').cardAfaMaxLimit).toEqual(MY_CARD_MAX_AMOUNT);
  expect(getCardLabelAndLimits('MY').cardTokenMaxAmount).toEqual(MY_CARD_MAX_AMOUNT);
});

test('getCardLabelText should return label text for curlec merchants', () => {
  const { container } = render(getCardLabelText('curlec'));
  expect(container.textContent).toBe('Maximum Auto-debit Amount');
});

test('getCardLabelText should return label for RZP merchants', () => {
  const { container } = render(getCardLabelText('rzp'));
  expect(container.textContent).toBe('Maximum Auto-debit Amount(For domestic cards only)');
});

test('shouldHideDebitPattern should return if debit pattern is hidden/shown', () => {
  expect(shouldHideDebitPattern(FREQUENCY.AS_PRESENTED)).toBeFalsy();
  expect(shouldHideDebitPattern(FREQUENCY.DAILY)).toBeFalsy();
  expect(shouldHideDebitPattern(FREQUENCY.MONTHLY)).toBeTruthy();
  expect(shouldHideDebitPattern(FREQUENCY.YEARLY)).toBeTruthy();
  expect(shouldHideDebitPattern(FREQUENCY.HALF_YEARLY)).toBeTruthy();
});

test('getBillingFrequencies should return expected frequencies', () => {
  const cardFrequencies = BILLING_FREQUENCY.filter(({ name }) => CARD_FREQUENCY.includes(name));
  expect(getBillingFrequencies(PAYMENT_METHODS.CARD)).toBeInstanceOf(Array);
  expect(getBillingFrequencies(PAYMENT_METHODS.CARD)).toStrictEqual(cardFrequencies);

  expect(getBillingFrequencies(PAYMENT_METHODS.UPI)).toBeInstanceOf(Array);
  expect(getBillingFrequencies(PAYMENT_METHODS.UPI)).toStrictEqual(BILLING_FREQUENCY);

  expect(getBillingFrequencies(PAYMENT_METHODS.EMANDATE)).toBeInstanceOf(Array);
  expect(getBillingFrequencies(PAYMENT_METHODS.EMANDATE)).toStrictEqual(BILLING_FREQUENCY);

  expect(getBillingFrequencies(PAYMENT_METHODS.NACH)).toBeInstanceOf(Array);
  expect(getBillingFrequencies(PAYMENT_METHODS.NACH)).toStrictEqual(BILLING_FREQUENCY);
});

// Assuming 'en-US' formatting rules apply
test('maxAmountValidator should validate default field limits', () => {
  expect(maxAmountValidator(100, MAX_TOKEN_AMOUNT)('invalid')).toContain('Invalid Amount');
  expect(
    maxAmountRenderer(maxAmountValidator(100, MAX_TOKEN_AMOUNT)(MAX_TOKEN_AMOUNT + 1)).container
      .textContent,
  ).toContain('Max amount should not be greater than ₹10,000,000.00');

  expect(
    maxAmountRenderer(maxAmountValidator(200, MAX_TOKEN_AMOUNT)(1)).container.textContent,
  ).toContain(
    'The maximum amount should be equal to or greater than ₹200.00, which is the minimum for this payment method.',
  );
});

test('maxAmountValidator should validate UPI field limits', () => {
  expect(maxAmountValidator(100, MAX_TOKEN_AMOUNT)('invalid')).toContain('Invalid Amount');

  expect(
    maxAmountRenderer(maxAmountValidator(100, UPI_AVL_LIMIT)(UPI_AVL_LIMIT + 1)).container
      .textContent,
  ).toContain('Max amount should not be greater than ₹200,000.00');

  expect(
    maxAmountRenderer(maxAmountValidator(200, UPI_AVL_LIMIT)(1)).container.textContent,
  ).toContain(
    'The maximum amount should be equal to or greater than ₹200.00, which is the minimum for this payment method.',
  );
});

test('maxAmountValidator should validate NACH field limits', () => {
  expect(maxAmountValidator(100, MAX_TOKEN_AMOUNT)('invalid')).toContain('Invalid Amount');

  expect(
    maxAmountRenderer(maxAmountValidator(100, MAX_TOKEN_AMOUNT_NACH)(MAX_TOKEN_AMOUNT_NACH + 1))
      .container.textContent,
  ).toContain('Max amount should not be greater than ₹10,000,000.00');

  expect(
    maxAmountRenderer(maxAmountValidator(200, MAX_TOKEN_AMOUNT_NACH)(1)).container.textContent,
  ).toContain(
    'The maximum amount should be equal to or greater than ₹200.00, which is the minimum for this payment method.',
  );
});

test('cardMaxAmountValidator should return proper error message for INR', () => {
  expect(
    maxAmountRenderer(
      cardMaxAmountValidator(CARD_TOKEN_MAX_AMOUNT, 'INR')(CARD_TOKEN_MAX_AMOUNT + 1),
    ).container.textContent,
  ).toContain('Please enter an amount below₹1,000,000.00');
});

test('cardMaxAmountValidator should return proper error message for MYR', () => {
  expect(
    maxAmountRenderer(cardMaxAmountValidator(MY_CARD_MAX_AMOUNT, 'MYR')(MY_CARD_MAX_AMOUNT + 1))
      .container.textContent,
  ).toContain('Please enter an amount belowMYR30,000.00');
});

describe('getMaxAmountProps', () => {
  const amount = 1000;
  it('should return correct props for wallet payment', () => {
    const props = getMaxAmountProps(
      PAYMENT_METHODS.WALLET,
      amount,
      {
        merchant: {
          country_code: 'MY',
          currency: 'MYR',
        },
      },
      0,
      {
        isWalletPayment: true,
      },
    );
    expect(props.description).toEqual(
      <>
        Max Amount for Mandate (Up to{' '}
        <Amount
          currency="MYR"
          value={convertToMajorUnit(DEFAULT_TOUCH_N_GO_MAX_LIMIT, { currency: 'MYR' })}
          type="body"
          size="small"
        />
        )
      </>,
    );
  });
});
