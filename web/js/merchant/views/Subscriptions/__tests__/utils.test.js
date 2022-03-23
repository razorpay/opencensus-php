import { isAmountLiesInRange } from '../utils';

test('Check If Amount Lies In the Range', () => {
  expect(isAmountLiesInRange(25, 10000, 1)).toBeTruthy();
  expect(isAmountLiesInRange('25', 10000, 1)).toBeTruthy();
  expect(isAmountLiesInRange(20)).toBeTruthy(); // Lies Between 1 & Infinity
  expect(isAmountLiesInRange(25, 100, 1)).toBeFalsy(); //Falsy because 25rs=2500 paisa
  expect(isAmountLiesInRange(-25, 100, 1)).toBeFalsy();
  expect(isAmountLiesInRange(undefined, 100, 1)).toBeFalsy();
});
