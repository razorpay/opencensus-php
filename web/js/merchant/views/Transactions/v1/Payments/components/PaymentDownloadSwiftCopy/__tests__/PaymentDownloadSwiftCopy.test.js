import downloadSwiftCopyColumn from 'merchant/views/Transactions/v1/Payments/components/PaymentDownloadSwiftCopy';
import { render, screen } from 'test-utils';

describe('Test downloadSwiftCopyColumn', () => {
  test('should return column object', () => {
    expect(downloadSwiftCopyColumn).toHaveProperty('title');
    expect(downloadSwiftCopyColumn).toHaveProperty('value');
  });

  test('should render download button in the column', () => {
    render(downloadSwiftCopyColumn.value({ id: 'pay_1232' }));
    expect(screen.getByRole('button')).toBeInTheDocument();
  });
});
