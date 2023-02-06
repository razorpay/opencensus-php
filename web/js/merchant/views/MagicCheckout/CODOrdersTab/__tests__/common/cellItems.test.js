import { reviewedBy } from 'merchant/views/MagicCheckout/CODOrdersTab/common/CellItems';

describe('testing cellItems functions', () => {
  test('should return email id if email not equal to automation_intelligence@razorpay.com', () => {
    const item = {
      reviewed_by: 'test@dummy.com',
    };
    expect(reviewedBy.value(item)).toBe('test@dummy.com');
  });

  test('should return automation if email is equal to automation_intelligence@razorpay.com', () => {
    const item = {
      reviewed_by: 'automation_intelligence@razorpay.com',
    };
    expect(reviewedBy.value(item)).toBe('Automation');
  });

  test('should return - if email is not present', () => {
    expect(reviewedBy.value({})).toBe('-');
  });
});
