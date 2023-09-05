import BatchListFilter from 'merchant/views/Transactions/v1/BatchRefunds/components/BatchListFilter';
import { render, screen } from 'test-utils';

const mockTrack = jest.fn();

jest.mock('merchant/views/Transactions/v1/AnalyticsTrack', () => ({
  handleChangeTrack: (_type) => (data) => mockTrack(data),
}));

const initProps = {
  form: 'batchListFilter',
  count: 25,
  onSubmit: jest.fn(),
};

describe('Refunds - RefundListFilter Component', () => {
  test('should render filter fields labels', () => {
    render(<BatchListFilter {...initProps} />);
    // asserting for field labels to be present
    ['Batch Upload Id', 'Count'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('should render filter fields inputs', () => {
    const { container } = render(<BatchListFilter {...initProps} />);
    // asserting fields input to be present
    ['id', 'count'].forEach((fieldInput) => {
      expect(container.querySelector(`input[name="${fieldInput}"]`)).toBeInTheDocument();
    });
  });
});
