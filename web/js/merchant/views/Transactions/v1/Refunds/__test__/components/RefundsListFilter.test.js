import { Provider } from 'react-redux';

import { storeWithInitialState } from 'merchant/store';
import { refundFilterInitState } from 'merchant/views/Transactions/v1/Refunds/__test__/mocks/fixtures';
import RefundsListFilter from 'merchant/views/Transactions/v1/Refunds/components/RefundsListFilter';
import { render, screen, fireEvent } from 'test-utils';

const mockTrack = jest.fn();

jest.mock('merchant/views/Transactions/v1/AnalyticsTrack', () => ({
  handleChangeTrack: (_type) => (data) => mockTrack(data),
}));

jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);

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

const App = ({ state = {}, ...props }) => {
  return (
    <Provider
      store={storeWithInitialState({
        ...refundFilterInitState,
        ...state,
      })}
    >
      <RefundsListFilter {...initProps} {...props} />
    </Provider>
  );
};

describe('Refunds - RefundListFilter Component', () => {
  test('should render filter fields labels', () => {
    render(<App />);
    // asserting for field labels to be present
    ['Refund Id', 'Duration', 'Payment Id', 'Status', 'Notes', 'Count'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('should render filter fields inputs', () => {
    const { container } = render(<App />);
    // asserting fields input to be present
    ['id', 'payment_id', 'notes', 'count'].forEach((fieldInput) => {
      expect(container.querySelector(`input[name="${fieldInput}"]`)).toBeInTheDocument();
    });

    // asserting status select field to be present
    expect(container.querySelector('select[name="public_status"]')).toBeInTheDocument();
  });

  test('shoudld call Analytics track with right type on filter fields input change', () => {
    const { container } = render(<App />);
    [
      {
        name: 'public_status',
        type: 'select',
        changed_value: 'processed',
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

  test('should call seacrh analytics and submit on search CTA', () => {
    render(<App />);
    const searchCTA = screen.getByRole('button', {
      name: 'Search',
    });
    expect(searchCTA).toBeInTheDocument();
    fireEvent.click(searchCTA);

    expect(onSubmitMock).toHaveBeenCalled();
    expect(onSearchAnalyticsMock).toHaveBeenCalled();
  });

  test('should call clear analytics on clear CTA', () => {
    render(<App />);
    const clearCTA = screen.getByRole('button', {
      name: 'Clear',
    });
    expect(clearCTA).toBeInTheDocument();
    fireEvent.click(clearCTA);
    expect(onClearAnalyticsMock).toHaveBeenCalled();
  });

  test('should not have status filter if it is false in state', () => {
    const { container } = render(
      <App
        state={{
          config: {
            config: {
              rs_filter: false,
            },
          },
        }}
      />,
    );
    expect(container.querySelector(`select[name="public_status"]`)).not.toBeInTheDocument();
  });
});
