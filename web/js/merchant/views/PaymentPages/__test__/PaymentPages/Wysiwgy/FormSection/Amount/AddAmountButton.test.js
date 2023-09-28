import { screen, render, userEvent } from 'test-utils';
import {
  renderApp,
  hideDynamicPriceField,
  userState,
} from 'merchant/views/PaymentPages/__test__/mocks/fixtures/AddAmountButton';
import store from 'merchant/store';
import AddAmountButton from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/FormSection/Amount/AddAmountButton';
import track from 'merchant/views/PaymentPages/PaymentPages/Wysiwyg/track';

const defaultProps = {
  currency: 'INR',
  countryCode: 'IN',
  isBatchPaymentPages: false,
};
const globalState = store.getState();

const dynamicAmountFieldLabel = 'Customers Decide Amount';

describe('Payment Page - Add Amount', () => {
  test('should have "Customers Decide Amount" if org feature flag "hide_dynamic_price_pp" is disabled.', () => {
    hideDynamicPriceField(false);
    renderApp();
    expect(screen.queryByText(dynamicAmountFieldLabel)).toBeInTheDocument();
  });

  test('should not have "Customers Decide Amount" if org feature flag "hide_dynamic_price_pp" is enabled.', () => {
    hideDynamicPriceField(true);
    renderApp();
    expect(screen.queryByText(dynamicAmountFieldLabel)).not.toBeInTheDocument();
  });
});

describe('Batch Payment Page - Add Amount', () => {
  beforeAll(() => {
    window.rzpQ = {
      paymentPages: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
    window.currencyList = {
      INR: {
        code: '356',
        denomination: 100,
        min_value: 100,
        min_auth_value: 100,
        symbol: '₹',
        name: 'Indian Rupee',
      },
    };
  });
  const renderApp = (initialState = {}, props = {}, showModal = false) => {
    render(<AddAmountButton {...defaultProps} {...props} />, {
      showModal,
      initialState: {
        ...globalState,
        session: {
          ...globalState.session,
          user: initialState?.session?.user ?? globalState?.session?.user,
          org: initialState?.session?.org ?? globalState?.session?.org,
        },
        wysiwyg: globalState.wysiwyg,
      },
      renderOptions: {
        initialEntries: ['/paymentpages/batchpaymentpages/new'],
        path: '/paymentpages/batchpaymentpages/new',
      },
    });
  };
  test('should able to add "Price Field" without selecting dynamic price if org feature flag "file_upload_pp" is enabled', async () => {
    track.wysiwyg.addPriceField = jest.fn();
    const initialState = {
      session: {
        user: userState,
      },
    };
    const props = {
      ...defaultProps,
      isBatchPaymentPages: true,
    };
    renderApp(initialState, props, true);
    const priceField = screen.getByText('Price field');
    expect(priceField).toBeInTheDocument();
    await userEvent.click(priceField);
    expect(track.wysiwyg.addPriceField).toHaveBeenCalled();
    expect(screen.getByText('Field title is required')).toBeInTheDocument();
    expect(screen.getByText('Additional Options')).toBeInTheDocument();
    expect(screen.getByText('Add Description')).toBeInTheDocument();
  });

  test('should able to add "Price Field" after selecting "Item with Quantity" if org feature flag "file_upload_pp" is disabled', async () => {
    const initialState = {
      session: {
        user: {
          ...userState,
          isPaymentPageFileUploadEnabled: false,
        },
      },
    };

    renderApp(initialState, defaultProps, true);
    const priceField = screen.getByText('Price field');
    expect(priceField).toBeInTheDocument();
    await userEvent.click(priceField);
    expect(track.wysiwyg.addPriceField).toHaveBeenCalled();
    expect(screen.getByText('Select Amount Type')).toBeInTheDocument();
    expect(screen.getByText('Fixed Amount')).toBeInTheDocument();
    expect(screen.getByText('Customers Decide Amount')).toBeInTheDocument();
    const itemWithQuantity = screen.getByText('Item with Quantity');
    expect(itemWithQuantity).toBeInTheDocument();
    await userEvent.click(itemWithQuantity);
    expect(screen.getByText('Please fill out this field')).toBeInTheDocument();
    expect(screen.getByText('Add Image')).toBeInTheDocument();
  });
});
