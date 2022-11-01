import BatchListFilter from 'merchant/views/Transactions/BatchRefunds/components/BatchListFilter';
import { render, screen, fireEvent } from 'test-utils';

const mockTrack = jest.fn();

jest.mock('merchant/views/Transactions/AnalyticsTrack', () => ({
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

  test('should call Analytics track with right type on filter fields input change', () => {
    const { container } = render(<BatchListFilter {...initProps} />);
    [
      {
        name: 'id',
        type: 'input',
        changed_value: 'lorem ipsum',
        track_type: 'search',
      },
    ].forEach((field, index) => {
      const fieldElement = container.querySelector(`${field.type}[name="${field.name}"]`);
      expect(fieldElement).toBeInTheDocument();
      fireEvent.change(fieldElement, {
        target: {
          value: field.changed_value,
        },
      });
      const trackCallNumber = index + 1;
      expect(mockTrack).toHaveBeenNthCalledWith(trackCallNumber, {
        args: expect.anything(),
        type: field.track_type,
      });
    });
  });
});
