import { CURRENCY_FORMATTERS } from 'merchant/helpers/currency/helper';

describe('Tests for currency formatting', () => {
  test('Test for three decimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.three(Number(1111111).toFixed(2), 2)).toBe('1,111,111.00');
  });

  test('Test for threecommadecimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threecommadecimal(Number(1111111).toFixed(2), 2)).toBe(
      '1.111.111,00',
    );
  });

  test('Test for threespaceseparator currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threespaceseparator(Number(1111111).toFixed(2), 2)).toBe(
      '1 111 111.00',
    );
  });

  test('Test for threespacecommadecimal currency formatting', () => {
    expect(CURRENCY_FORMATTERS.threespacecommadecimal(Number(1111111).toFixed(2), 2)).toBe(
      '1 111 111,00',
    );
  });

  test('Test for szl currency formatting', () => {
    expect(CURRENCY_FORMATTERS.szl(Number(1111111).toFixed(2), 2)).toBe('1, 111, 111.00');
  });

  test('Test for chf currency formatting', () => {
    expect(CURRENCY_FORMATTERS.chf(Number(1111111).toFixed(2), 2)).toBe(`1'111'111.00`);
  });

  test('Test for inr currency formatting', () => {
    expect(CURRENCY_FORMATTERS.inr(Number(1111111).toFixed(2), 2)).toBe('11,11,111.00');
  });

  test('Test for myr currency formatting', () => {
    expect(CURRENCY_FORMATTERS.myr(Number(1111111).toFixed(2), 2)).toBe('11,11,111.00');
  });

  test('Test for none currency formatting', () => {
    expect(CURRENCY_FORMATTERS.none(Number(1111111).toFixed(2), 2)).toBe('1111111.00');
  });
});
