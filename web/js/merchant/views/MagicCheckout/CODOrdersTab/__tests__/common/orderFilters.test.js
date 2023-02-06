import { render, screen, fireEvent, waitFor } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import { Router } from 'react-router-dom';
import { ThemeProvider } from 'styled-components';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import { createMemoryHistory } from 'history';
import OrderFilters from 'merchant/views/MagicCheckout/CODOrdersTab/common/OrderFilters';

const initState = {
  magicCODOrders: {
    orderFiltersData: {
      onSubmitHandler: jest.fn(),
      resetHandler: jest.fn(),
      onDatesChange: jest.fn(),
      orderFiltersData: {
        id: '',
        count: '',
        riskTier: '',
        selectedPresetFromParent: false,
      },
      updateFilters: jest.fn(),
    },
  },
};

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...initState, ...state })}>
      <OrderFilters {...props} />
    </Provider>
  );
};

const AppWithRouter = ({ state = {}, ...props }) => {
  return (
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <ThemeProvider theme={theme}>
        <App state={state} {...props} />
      </ThemeProvider>
    </Router>
  );
};

describe('Orders Filter component', () => {
  beforeAll(() => {
    window.rzpQ = {
      component: jest.fn(),
      merchantActions: jest.fn(() => ({ success: jest.fn(), initiated: jest.fn() })),
    };
  });

  test('rendering orders filter except review mode filter', async () => {
    const filters = ['razorpay order id', 'receipt', 'rto risk', 'count'];
    render(<AppWithRouter />);
    await waitFor(() => {
      filters.forEach((item) => {
        expect(screen.getByLabelText(new RegExp(`${item}`, 'i'))).toBeInTheDocument();
      });

      expect(screen.queryByLabelText(/review mode/i)).not.toBeInTheDocument();
    });
  });

  test('should set filter value when input changes', () => {
    const { container } = render(<App showReviewModeFilter />);
    [
      {
        name: 'id',
        type: 'input',
        changed_value: 'Rzp123',
      },
      {
        name: 'count',
        type: 'select',
        changed_value: '25',
      },
      {
        name: 'receipt',
        type: 'input',
        changed_value: '123',
      },
      {
        name: 'riskTier',
        type: 'select',
        changed_value: 'high',
      },
      {
        name: 'reviewMode',
        type: 'select',
        changed_value: 'automation',
      },
    ].forEach((field) => {
      const fieldElement = container.querySelector(`${field.type}[name="${field.name}"]`);
      expect(fieldElement).toBeInTheDocument();
      fireEvent.change(fieldElement, {
        target: {
          value: field.changed_value,
        },
      });
      expect(fieldElement.value).toBe(field.changed_value);
    });
  });
});
