import ICProducts from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/ICProducts';
import { fetchScheduleHandler } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/__test__/mocks/handlers';
import {
  ICProductStates,
  ProductTypeForAnalytics,
} from 'merchant/views/AccountAndSettings/PaymentMethods/typings';
import React from 'react';
import { render, screen, server, userEvent, waitFor } from 'test-utils';

jest.mock(
  'merchant/views/Settings/PaymentMethods/components/Non3dsCardsActivation/Non3dsCardsActivation',
  () => ({
    __esModule: true,
    default: ({ isIERevamp }) => (
      <div>Non3dsCardsActivation Component: {isIERevamp ? 'true' : 'false'}</div>
    ),
  }),
);

jest.mock(
  'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/ICProductInfo',
  () => ({
    __esModule: true,
    default: ({
      title,
      description,
      settlementCycle,
      transactionSize,
      status,
      product,
      onRequestAccessClick,
    }) => (
      <div data-testid={`ic-product-info-${product}`}>
        <p>{title}</p>
        <p>{description}</p>
        <p>Settlement cycle: {settlementCycle}</p>
        <p>Transaction size: {transactionSize}</p>
        <p>Status: {status}</p>
        <button type="button" onClick={() => onRequestAccessClick(product)}>
          {`Request ${product}`}
        </button>
      </div>
    ),
  }),
);

const defaultProps = {
  onRequestAccessClick: jest.fn(),
  pgProductState: ICProductStates.ACTION_REQUIRED,
  ppliProductState: ICProductStates.REJECTED,
};

const renderApp = () =>
  render(<ICProducts {...defaultProps} />, {
    initialState: {
      session: {
        user: {
          merchant: {
            max_payment_amount: 100,
          },
        },
      },
    },
  });

const validateICProductContent = async ({ title, description, status, product, triggerSource }) => {
  const iCProductElement = screen.getByTestId(`ic-product-info-${product}`);
  expect(iCProductElement).toHaveTextContent(title);
  expect(iCProductElement).toHaveTextContent(description);
  expect(iCProductElement).toHaveTextContent(`Transaction size: 100`);
  expect(iCProductElement).toHaveTextContent(`Status: ${status}`);

  const requestButton = screen.getByRole('button', { name: `Request ${product}` });
  await userEvent.click(requestButton);
  expect(defaultProps.onRequestAccessClick).toHaveBeenCalledWith(triggerSource);

  await waitFor(() => {
    expect(iCProductElement).toHaveTextContent(`Settlement cycle: 7`);
  });
};

describe('ICProducts', () => {
  beforeEach(() => {
    server.use(fetchScheduleHandler());
  });

  test('should render Non3dsCardsActivation, pg and ppli ICProducts Info', () => {
    renderApp();
    expect(screen.getByText('Non3dsCardsActivation Component: true')).toBeInTheDocument();
  });

  test('should render PG IC Product Info', async () => {
    renderApp();
    await validateICProductContent({
      title: 'Payment Gateway',
      description:
        'Activate international card payments on payment gateway after website registration',
      status: ICProductStates.ACTION_REQUIRED,
      product: ProductTypeForAnalytics.PG,
      triggerSource: 'pg',
    });
  });

  test('should render PPLI IC Product Info', async () => {
    renderApp();
    await validateICProductContent({
      title: 'Payment pages, payment links, and invoices',
      description:
        'Activate international card payments on payment pages, payment links, and invoices without website requirement',
      status: ICProductStates.REJECTED,
      product: ProductTypeForAnalytics.PPLI,
      triggerSource: 'others',
    });
  });
});
