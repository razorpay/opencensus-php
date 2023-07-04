import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import OrderFilters from 'merchant/views/MagicCheckout/CODToPrepaid/CODToPrepaidLinks/components/OrderFilters';

jest.mock('common/ui/DateRangePicker', () => (props) => {
  const { onSelectPreset, isOutsideRange } = props;

  return (
    <div>
      <p>DateRangePicker</p>
      <button type="button" onClick={onSelectPreset}>
        Select preset
      </button>
      <button type="button" onClick={isOutsideRange}>
        Is valid date
      </button>
    </div>
  );
});

const initState = {
  magicCODOrders: {
    id: '',
    receipt: '',
    riskTier: '',
    count: 25,
    selectedPresetFromParent: null,
    paymentLinkStatus: '',
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <OrderFilters {...props} />
    </Provider>
  );
};

describe('order filter component', () => {
  test.each(['razorpay order id', 'count', 'duration', 'conversion status', 'receipt'])(
    'filters should render properly',
    (fieldLabel) => {
      render(<App />);
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    },
  );

  test('show rto risk filter if manual review is opted', () => {
    render(<App showRTORisk />);
    expect(screen.getByText(/RTO risk/i)).toBeInTheDocument();
  });

  test('should aloow to search via Razorpay order id', async () => {
    render(<App />);
    const razorpayOrderIdFilter = screen.getByRole('textbox', {
      name: /^Razorpay Order Id?/i,
    });
    const orderId = 'test_id_123';
    await userEvent.type(razorpayOrderIdFilter, orderId);
    expect(razorpayOrderIdFilter).toHaveAttribute('value', orderId);
  });

  test('should select preset from date', async () => {
    render(<App />);
    const selectPresetCTA = screen.getByRole('button', {
      name: 'Select preset',
    });

    await userEvent.click(selectPresetCTA);
  });
});
