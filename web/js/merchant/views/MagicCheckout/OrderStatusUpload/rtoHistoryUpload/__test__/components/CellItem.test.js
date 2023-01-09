import {
  fileId,
  shippingProvider,
  createdAt,
  status,
} from 'merchant/views/MagicCheckout/OrderStatusUpload/rtoHistoryUpload/components/CellItem';

describe('testing for cellItem functions', () => {
  test('should return status label class when function is called', () => {
    const statusLabel = status.value();
    expect(statusLabel.type).toBe('span');
  });

  test('should return some value when function is called', () => {
    [
      {
        function: fileId,
        arg: { file_id: 123 },
        returnValue: 123,
      },
      {
        function: shippingProvider,
        arg: { shipping_provider: 'delhivery' },
        returnValue: 'Delhivery',
      },
      {
        function: createdAt,
        arg: { created_at: 1671610651 },
        returnValue: '21 Dec 2022',
      },
    ].forEach((item) => {
      const value = item.function.value(item.arg);
      expect(value).toBe(item.returnValue);
    });
  });

  test('should return empty string when value is undefined', () => {
    [fileId, shippingProvider, createdAt].forEach((actionItem) => {
      const value = actionItem.value({});
      expect(value).toBe('-');
    });
  });
});
