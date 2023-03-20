import { storeWithInitialState } from 'merchant/store';
import OrderFilters from 'merchant/views/MagicCheckout/CODOrdersTab/common/OrderFilters';
import 'react-dates/initialize';
import { Provider } from 'react-redux';
import { render, screen, userEvent, waitFor } from 'test-utils';

jest.mock('common/ui/DateRangePicker', () => () => <div>DateRangePicker</div>);

const INPUT_FIELDS = [
  {
    name: 'Razorpay order id',
    type: 'input',
    changed_value: 'Rzp123',
  },
  {
    name: 'count',
    type: 'select',
    changed_value: '25',
    shownValue: '25',
  },
  {
    name: 'receipt',
    type: 'input',
    changed_value: '123',
  },
  {
    name: 'RTO risk',
    type: 'select',
    changed_value: 'high',
    shownValue: 'High Risk',
  },
  {
    name: 'Review mode',
    type: 'select',
    changed_value: 'automation',
    shownValue: 'Automation',
  },
];

const initState = {
  magicCODOrders: {
    id: '',
    count: '',
    riskTier: '',
    selectedPresetFromParent: false,
    receipt: '',
    reviewMode: '',
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <OrderFilters {...props} />
    </Provider>
  );
};

const FILTERS = ['razorpay order id', 'receipt', 'rto risk', 'count'];

describe('Orders Filter component', () => {
  test.each(FILTERS)('rendering orders filter except review mode filter', async (item) => {
    render(<App />);
    if (item === 'review mode') {
      expect(screen.getByLabelText(new RegExp(/review mode/i))).not.toBeInTheDocument();
    } else {
      await waitFor(() => {
        expect(screen.getByLabelText(new RegExp(`${item}`, 'i'))).toBeInTheDocument();
      });
    }
  });

  //TODO: date range picker is not getting mocked properly. Giving error ' Element type is invalid. Received a promise that resolves to: undefined. Lazy element type must resolve to a class or function.'
  test.skip.each(INPUT_FIELDS)('should set filter value when input changes', async (field) => {
    render(<App showReviewModeFilter />);
    const fieldElement = screen.getByLabelText(new RegExp(field.name, 'i'));
    if (field.type === 'input') {
      await userEvent.type(fieldElement, field.changed_value);
      expect(fieldElement).toHaveAttribute('value', field.changed_value);
    } else {
      await userEvent.selectOptions(fieldElement, field.changed_value);
      expect(screen.getByRole('option', { name: field.shownValue }).selected).toBe(true);
    }
  });
});
