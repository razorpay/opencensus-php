import {
  isAmountLiesInRange,
  isMonthlyDebitPattern,
  getDebitPatternDesc,
} from 'merchant/views/Subscriptions/utils';
import { FREQUENCY } from 'merchant/views/Subscriptions/constants';

test('Check If Amount Lies In the Range', () => {
  expect(isAmountLiesInRange(25, 10000, 1)).toBeTruthy();
  expect(isAmountLiesInRange('25', 10000, 1)).toBeTruthy();
  expect(isAmountLiesInRange(20)).toBeTruthy(); // Lies Between 1 & Infinity
  expect(isAmountLiesInRange(25, 100, 1)).toBeFalsy(); //Falsy because 25rs=2500 paisa
  expect(isAmountLiesInRange(-25, 100, 1)).toBeFalsy();
  expect(isAmountLiesInRange(undefined, 100, 1)).toBeFalsy();
});

test('Test if getDebitPatternDesc returns expected strings', () => {
  expect(isMonthlyDebitPattern(FREQUENCY.AS_PRESENTED)).toBeFalsy();
  expect(isMonthlyDebitPattern(FREQUENCY.DAILY)).toBeFalsy();
  expect(isMonthlyDebitPattern(FREQUENCY.WEEKLY)).toBeFalsy();
  expect(isMonthlyDebitPattern(FREQUENCY.FORTNIGHTLY)).toBeFalsy();

  expect(isMonthlyDebitPattern(FREQUENCY.BIMONTHLY)).toBeTruthy();
  expect(isMonthlyDebitPattern(FREQUENCY.MONTHLY)).toBeTruthy();
  expect(isMonthlyDebitPattern(FREQUENCY.QUARTERLY)).toBeTruthy();
  expect(isMonthlyDebitPattern(FREQUENCY.HALF_YEARLY)).toBeTruthy();
  expect(isMonthlyDebitPattern(FREQUENCY.YEARLY)).toBeTruthy();
});

test('Test if isMonthlyDebitPattern returns true for expected frequencies', () => {
  const expectedDescription = (range) =>
    `Enter a value between ${range} corresponding to days of a week`;
  expect(getDebitPatternDesc(FREQUENCY.AS_PRESENTED)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.DAILY)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.WEEKLY)).toEqual(expectedDescription('1-7'));

  expect(getDebitPatternDesc(FREQUENCY.FORTNIGHTLY)).toEqual(expectedDescription('1-15'));
  expect(getDebitPatternDesc(FREQUENCY.BIMONTHLY)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.MONTHLY)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.QUARTERLY)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.HALF_YEARLY)).toEqual(expectedDescription('1-31'));
  expect(getDebitPatternDesc(FREQUENCY.YEARLY)).toEqual(expectedDescription('1-31'));
});
