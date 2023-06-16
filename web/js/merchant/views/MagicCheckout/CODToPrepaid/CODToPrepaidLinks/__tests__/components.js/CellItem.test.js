import {
  paymentLinkAction,
  paymentLinkStatus,
  paymentLinkOrderId,
} from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/CellItem';

describe('testing cell items', () => {
  test('paymentOrderid should return link as an id', () => {
    const item = { id: 'test_order_123' };
    const ele = paymentLinkOrderId.value(item);
    expect(ele.props.to).toBe('?order_id=test_order_123');
  });

  test('payment link action should return null when nothing pl status is not sent', () => {
    const item = { magic_payment_link: { id: '123', status: 'paid' } };
    const ele = paymentLinkAction().value(item);
    expect(ele).toBe('--');
  });

  test('should show action icon if pl status is sent', () => {
    const item = { magic_payment_link: { id: '123', status: 'sent' } };
    const ele = paymentLinkAction().value(item);
    expect(ele).not.toBe('--');
  });

  test("don't show the payment link status", () => {
    const item = { magic_payment_link: { id: '123' } };
    const ele = paymentLinkStatus.value(item);
    expect(ele).toBe('-');
  });

  test('show the payment link status', () => {
    const item = { magic_payment_link: { id: '123', status: 'expired' } };
    const ele = paymentLinkStatus.value(item);
    expect(ele).not.toBe('-');
  });
});
