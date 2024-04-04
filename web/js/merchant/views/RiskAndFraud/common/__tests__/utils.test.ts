import { replaceBusinessName } from '../utils';

describe('replaceBusinessName', () => {
  it('should replace _businessName_ with the provided business name', () => {
    const result = replaceBusinessName({
      str: 'https://_businessName_.com/docs/payments/payments/risk-visibility-dashboard/fraud-sales-ratio/#calculating-fraud-to-sales-ratio',
      businessName: 'razorpay',
    });
    expect(result).toBe(
      'https://razorpay.com/docs/payments/payments/risk-visibility-dashboard/fraud-sales-ratio/#calculating-fraud-to-sales-ratio',
    );
  });

  it('should replace _businessName_ with the provided business name in uppercase', () => {
    const result = replaceBusinessName({
      str: 'A risk decline occurs when the algorithm (_businessName_, bank or network) declines or blocks risky transactions that might be potentially fraudulent or have high likelihood of being disputed. It is calculated as:',
      businessName: 'razorpay',
      isCaps: true,
    });
    expect(result).toBe(
      'A risk decline occurs when the algorithm (Razorpay, bank or network) declines or blocks risky transactions that might be potentially fraudulent or have high likelihood of being disputed. It is calculated as:',
    );
  });

  it('should replace _businessName_ with "razorpay" if no business name is provided', () => {
    const result = replaceBusinessName({
      str: 'https://_businessName_.com/docs/payments/payments/risk-visibility-dashboard/fraud-sales-ratio/#calculating-fraud-to-sales-ratio',
      businessName: '',
    });
    expect(result).toBe(
      'https://razorpay.com/docs/payments/payments/risk-visibility-dashboard/fraud-sales-ratio/#calculating-fraud-to-sales-ratio',
    );
  });

  it('should return the original string if no string is provided', () => {
    const result = replaceBusinessName({
      str: '',
      businessName: 'razorpay',
    });
    expect(result).toBe('');
  });
});
