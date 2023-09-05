import OrdersListFilter from 'merchant/views/Transactions/v1/Orders/components/OrdersListFilter';
import { render, fireEvent, screen } from 'test-utils';

const mockTrack = jest.fn();

jest.mock('merchant/views/Transactions/v1/AnalyticsTrack', () => ({
  handleChangeTrack: (_type) => (data) => mockTrack(data),
}));

const onSubmitMock = jest.fn();
const onSearchAnalyticsMock = jest.fn();
const onClearAnalyticsMock = jest.fn();

const initProps = {
  form: 'orderListFilter',
  count: 100,
  onSubmit: onSubmitMock,
  onSearchAnalytics: onSearchAnalyticsMock,
  onClearAnalytics: onClearAnalyticsMock,
};

describe('Orders - OrdersListFilter component', () => {
  it('should render filter fields labels', () => {
    render(<OrdersListFilter {...initProps} />);
    // asserting for field labels to be present
    ['Order Id', 'Receipt', 'Notes', 'Status', 'Count'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  it('should render filter fields inputs', () => {
    const { container } = render(<OrdersListFilter {...initProps} />);
    // asserting fields input to be present
    ['id', 'receipt', 'notes', 'count'].forEach((fieldInput) => {
      expect(container.querySelector(`input[name="${fieldInput}"]`)).toBeInTheDocument();
    });

    // asserting status select field to be present
    expect(container.querySelector('select[name="status"]')).toBeInTheDocument();
  });

  it('shoudld call Analytics track with right type on filter fields input change', () => {
    const { container } = render(<OrdersListFilter {...initProps} />);
    [
      {
        name: 'status',
        type: 'select',
        changed_value: 'Paid',
        track_type: 'filter',
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
