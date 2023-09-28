import { testBreadCrumb } from 'merchant/views/AccountAndSettings/__test__/mocks/fixtures';
import PaymentMethods from 'merchant/views/AccountAndSettings/PaymentMethods';
import { render, screen, waitFor, userEvent, errorHandlers, server } from 'test-utils';
import { ROUTES_INFO } from 'merchant/views/AccountAndSettings/typings/routes';
import { PaymentMethodsTitles } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings/section';
import * as instrumentRequests from 'merchant/reducers/instrumentRequests';
import * as analytics from 'common/utils/analytics';
import 'jest-location-mock';
import { PaymentMethodsTabsRoutesConfig } from 'merchant/views/AccountAndSettings/PaymentMethods/constants';
import { createMemoryHistory } from 'history';

jest.mock('merchant/views/Settings/PaymentMethods', () => ({
  __esModule: true,
  default: () => <div data-testid="/payment-methods">Payment Methods Component</div>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/Cards', () => ({
  __esModule: true,
  default: () => <>Cards</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/Emi', () => ({
  __esModule: true,
  default: () => <>Emi</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/International', () => ({
  __esModule: true,
  default: () => <>International</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/Netbanking', () => ({
  __esModule: true,
  default: () => <>Netbanking</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/UpiQR', () => ({
  __esModule: true,
  default: () => <>UpiQR</>,
}));
jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/Wallet', () => ({
  __esModule: true,
  default: () => <>Wallet</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/Paylater', () => ({
  __esModule: true,
  default: () => <>Paylater</>,
}));

jest.mock('merchant/views/AccountAndSettings/PaymentMethods/Tabs/MealCard', () => ({
  __esModule: true,
  default: () => <>Meal Card/Sodexo</>,
}));

jest.mock('common/components/Loader', () => ({
  __esModule: true,
  default: () => <div data-testid="loader" />,
}));

const setInstrumentSpy = jest.spyOn(instrumentRequests, 'setInstrument');
const clearInstrumentSpy = jest.spyOn(instrumentRequests, 'clearLeafInstrument');
const clearIntermediateInstrumentSpy = jest.spyOn(
  instrumentRequests,
  'clearIntermediateInstrument',
);
const analyticsTrackSpy = jest.spyOn(analytics, 'analyticsTrackWithUserInfo');

const instrumentsList = instrumentRequests.initialState.pg.map((instrument) => instrument.name);

const renderApp = (userObj = {}, props = {}, other = {}) => {
  const { isIERevampEnabled = true, isSodexoInstrumentEnabled = true } = userObj;
  return render(<PaymentMethods {...props} />, {
    initialState: {
      session: {
        user: {
          isIERevampEnabled,
          isSodexoInstrumentEnabled,
        },
        org: {},
      },
    },
    initialEntries: [ROUTES_INFO.INTERNATIONAL_PAYMENTS],
    ...other,
  });
};

describe('PaymentMethods', () => {
  describe('When IE Revamp is false', () => {
    const renderAppIERevampFalse = () =>
      renderApp(
        { isIERevampEnabled: false },
        {},
        {
          initialEntries: [ROUTES_INFO.PAYMENT_METHODS],
        },
      );

    test('should render loader and not payment method shimmer', () => {
      renderAppIERevampFalse();
      expect(screen.getByTestId('loader')).toBeInTheDocument();
      expect(screen.queryByTestId('payment-method-tabs-shimmer')).not.toBeInTheDocument();
    });

    test('should render PaymentMethods link', () => {
      renderAppIERevampFalse();
      const paymentMethodsLink = screen.getByRole('link', { name: 'Payment Methods' });
      expect(paymentMethodsLink).toBeInTheDocument();
      expect(paymentMethodsLink).toHaveAttribute('href', ROUTES_INFO.PAYMENT_METHODS);
    });

    testBreadCrumb(renderAppIERevampFalse, 'Payment Methods', ROUTES_INFO.PAYMENT_METHODS);

    test('should render PaymentMethods for Payment Methods route', () => {
      renderAppIERevampFalse();
      const paymentMethodComponent = screen.getByTestId(ROUTES_INFO.PAYMENT_METHODS);
      expect(paymentMethodComponent).toBeInTheDocument();
      expect(paymentMethodComponent).toHaveTextContent('Payment Methods Component');
    });

    test.each(instrumentsList)('should not render %s link', (instrument) => {
      renderAppIERevampFalse();
      expect(screen.queryByRole('link', { name: instrument })).not.toBeInTheDocument();
    });
  });

  describe('When IE Revamp is true', () => {
    test('should render payment method shimmer and not loader', () => {
      renderApp();
      expect(screen.getByTestId('payment-method-tabs-shimmer')).toBeInTheDocument();
      expect(screen.queryByTestId('loader')).not.toBeInTheDocument();
    });

    testBreadCrumb(
      renderApp,
      PaymentMethodsTitles.international,
      ROUTES_INFO.INTERNATIONAL_PAYMENTS,
    );

    test.each(instrumentsList)('should render %s link', (instrument) => {
      renderApp();
      expect(screen.getByRole('link', { name: instrument })).toBeInTheDocument();
    });

    // Test to check if 'Meal Card/Sodexo' is not rendered when isSodexoInstrumentEnabled is false
    test('should not render "Meal Card/Sodexo" when isSodexoInstrumentEnabled is false', () => {
      renderApp({ isSodexoInstrumentEnabled: false });
      expect(screen.queryByRole('link', { name: 'Meal Card/Sodexo' })).not.toBeInTheDocument();
    });

    test.each([
      ['Cards', ROUTES_INFO.CARDS],
      ['UpiQR', ROUTES_INFO.UPI_QR],
      ['Netbanking', ROUTES_INFO.NETBANKING],
      ['Emi', ROUTES_INFO.EMI],
      ['Wallet', ROUTES_INFO.WALLET],
      ['Paylater', ROUTES_INFO.PAY_LATER],
      ['International', ROUTES_INFO.INTERNATIONAL_PAYMENTS],
      ['Meal Card/Sodexo', ROUTES_INFO.MEAL_CARD],
    ])('should render %s component for %s link', async (content, path) => {
      renderApp();
      await waitFor(() => {
        expect(screen.queryByTestId('payment-method-tabs-shimmer')).not.toBeInTheDocument();
      });
      expect(screen.getByTestId(`${path.replace('/payment-methods/', '')}/*`)).toHaveTextContent(
        content,
      );
    });

    test('should not render payment methods link', () => {
      renderApp();
      expect(screen.queryByRole('link', { name: 'Payment Methods' })).not.toBeInTheDocument();
    });

    test('should set International Payments instrument after loading', async () => {
      renderApp();
      await waitFor(() => {
        expect(setInstrumentSpy).toHaveBeenCalledWith(
          expect.objectContaining({
            description: 'Cards, Paypal, USD ACH & more',
            name: 'International Payments',
            slug: 'international',
          }),
        );
      });
    });

    test('should set new instrument and call analytics on link click', async () => {
      renderApp();
      const cardsLink = screen.getByRole('link', { name: 'Cards' });
      await userEvent.click(cardsLink);
      expect(clearInstrumentSpy).toHaveBeenCalled();
      expect(clearIntermediateInstrumentSpy).toHaveBeenCalled();
      expect(analyticsTrackSpy).toHaveBeenCalledWith({
        objectName: 'method',
        actionName: 'clicked',
        screen: 'settings',
        properties: {
          method: 'Cards',
        },
      });
      expect(setInstrumentSpy).toHaveBeenCalledWith(
        expect.objectContaining({
          description: 'Visa, Master, Amex',
          name: 'Cards',
          slug: 'cards',
        }),
      );
    });

    test('should show error notification on fetchInstruments fail', async () => {
      server.use(errorHandlers.internalServerError);
      renderApp();
      await waitFor(() => {
        expect(screen.getByText('Something went wrong')).toBeInTheDocument();
      });
    });

    describe('On opening older links', () => {
      beforeEach(() => {
        window.location.assign.mockClear();
      });

      test('should redirect to newer international link when older international link is opened', async () => {
        const pathname = `${ROUTES_INFO.PAYMENT_METHODS}?instrument=international`;
        window.location.assign(pathname);
        const history = createMemoryHistory({ initialEntries: [pathname] });
        history.replace = jest.fn();
        renderApp(undefined, undefined, { history });

        await waitFor(() => {
          expect(history.replace).toHaveBeenLastCalledWith(
            { hash: '', pathname: PaymentMethodsTabsRoutesConfig.international, search: '' },
            undefined,
            { replace: true, state: undefined },
          );
        });
      });

      test('should redirect to first instrument when older cards link is opened without any instrument query param', async () => {
        const pathname = ROUTES_INFO.PAYMENT_METHODS;
        const history = createMemoryHistory({ initialEntries: [pathname] });
        history.replace = jest.fn();
        renderApp(undefined, undefined, { history });

        await waitFor(() => {
          expect(history.replace).toHaveBeenLastCalledWith(
            {
              hash: '',
              pathname: PaymentMethodsTabsRoutesConfig[instrumentRequests.initialState.pg[0].slug],
              search: '',
            },
            undefined,
            { replace: true, state: undefined },
          );
        });
      });
    });
  });

  test('should render dashboard banner and test banner', () => {
    renderApp();
    expect(screen.getByText('Dashboard Banner')).toBeInTheDocument();
    expect(screen.getByText('Test Mode Banner')).toBeInTheDocument();
  });
});
