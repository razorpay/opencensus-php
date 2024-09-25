import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { formatXAxis, getActionWidgetIcon, makeLink } from 'merchant/widgets/common/utils';

describe('Widget->common->utils->makeLink', () => {
  test('should return correct url for additional_website', () => {
    const url = makeLink('additional_website');
    expect(url).toBe(ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS);
  });
  test('should return correct url for add_additional_website', () => {
    const url = makeLink('add_additional_website');
    expect(url).toBe(ROUTES_INFO.BUSINESS_WEBSITE_SETTINGS);
  });
  test('should return correct url for increase_international_transaction_limit', () => {
    const url = makeLink('increase_international_transaction_limit');
    expect(url).toBe(ROUTES_INFO.INTERNATIONAL_PAYMENTS);
  });
  test('should return correct url for international', () => {
    const url = makeLink('international');
    expect(url).toBe(ROUTES_INFO.INTERNATIONAL_PAYMENTS);
  });
  test('should return correct url for toggle_international_revamped', () => {
    const url = makeLink('toggle_international_revamped');
    expect(url).toBe(ROUTES_INFO.INTERNATIONAL_PAYMENTS);
  });
  test('should return correct url for increase_transaction_limit', () => {
    const url = makeLink('increase_transaction_limit');
    expect(url).toBe(ROUTES_INFO.TRANSACTION_LIMITS);
  });
  test('should return correct url for gstin_update_self_serve', () => {
    const url = makeLink('gstin_update_self_serve');
    expect(url).toBe(ROUTES_INFO.GST_DETAILS);
  });
  test('should return correct url for bank_detail_update', () => {
    const url = makeLink('bank_detail_update');
    expect(url).toBe(ROUTES_INFO.BANK_ACCOUNT_DETAILS);
  });
  test('should return correct url for support_ticket', () => {
    const url = makeLink('support_ticket');
    expect(url).toBe(ROUTES_INFO.SUPPORT_TICKETS_MERCHANT);
  });
  test('should return correct url for payment_details', () => {
    const url = makeLink('payment_details', { payment_id: '123' });
    expect(url).toBe('/payments/123');
  });
  test('should return correct url for refund_details', () => {
    const url = makeLink('refund_details', { refund_id: '123' });
    expect(url).toBe('/refunds/123');
  });
  test('should return empty string is refund_id key in not present', () => {
    const url = makeLink('refund_details', {});
    expect(url).toBe('/refunds/');
  });
  test('should return correct url for payment_failed', () => {
    const url = makeLink('payment_failed', { from: 123, to: 456, status: 'failed' });
    expect(url).toBe('/failed-payments');
  });
  test('should return correct url for refund_failed', () => {
    const url = makeLink('refund_failed', { from: 123, to: 456, public_status: 'failed' });
    expect(url).toBe('/refunds?from=123&to=456&public_status=failed');
  });
  test('should return null if key does not match', () => {
    const url = makeLink('invalid');
    expect(url).toBe(null);
  });
});

describe('Widget->common->utils->getActionWidgetIcon', () => {
  test('should return undefined for invalid type', () => {
    const icon = getActionWidgetIcon('invalid');
    expect(icon).toBe(undefined);
  });
});

describe('Widget->common->utils->formatXAxis', () => {
  test('should return hour and minute for today', () => {
    const formattedXAxisLabel = formatXAxis('1709271000', { type: 'timestamp', unit: '' }, 'today');
    expect(formattedXAxisLabel).toBe('05:30 AM');
  });

  test('should return date for last_7_days', () => {
    const formattedXAxisLabel = formatXAxis(
      '1708626600',
      { type: 'timestamp', unit: '' },
      'last_7_days',
    );
    expect(formattedXAxisLabel).toBe('Feb 22');
  });

  test('should return start and end date of week for last_30_days', () => {
    const formattedXAxisLabel = formatXAxis(
      '1706466600',
      { type: 'timestamp', unit: '' },
      'last_30_days',
    );
    expect(formattedXAxisLabel).toBe('Jan 28 - Feb 04');
  });
});
