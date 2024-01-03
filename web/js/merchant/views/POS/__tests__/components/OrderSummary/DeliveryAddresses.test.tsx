import React from 'react';

import DeliveryAddresses from 'merchant/views/POS/OrderSummary/DeliveryAddresses';
import {
  MOCK_ADDRESSES,
  MOCK_USER,
  MOCK_PRICING_PLAN,
  MOCK_GTM,
} from 'merchant/views/POS/__tests__/mocks/fixtures';
import {
  getPincodeInfoHandler,
  getProductPricingHandler,
} from 'merchant/views/POS/__tests__/mocks/handlers';
import { PosDeviceStoreProvider } from 'merchant/views/POS/providers';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForElementToBeRemoved,
  within,
} from 'test-utils';

jest.mock('common/splitz', () => ({
  ...(jest.requireActual('common/splitz') as Record<string, string>),
  useSplitzService: () => ({
    abExperiments: MOCK_GTM,
  }),
}));

jest.mock('merchant/views/POS/constants', () => {
  const actual = jest.requireActual('merchant/views/POS/constants') as Record<string, string>;
  const mockProduct = jest.requireActual(
    'merchant/views/POS/__tests__/mocks/fixtures',
  ).MOCK_PRODUCT;

  return {
    ...actual,
    PRODUCT_DESCRIPTIONS: {
      'mock-product': mockProduct,
    },
    pincodeValidationSchema: () => ({}),
  };
});

jest.mock('merchant/views/POS/helpers', () => ({
  ...(jest.requireActual('merchant/views/POS/helpers') as Record<string, string>),
  getAllDeliveryAddressFromLocalStorage: () => MOCK_ADDRESSES,
}));

jest.mock('merchant/views/POS/services', () => ({
  ...(jest.requireActual('merchant/views/POS/services') as Record<string, string>),
  getPricingPlan: () => MOCK_PRICING_PLAN,
}));

const renderApp = () => {
  render(
    <PosDeviceStoreProvider user={MOCK_USER}>
      <DeliveryAddresses defaultIsExpanded />
    </PosDeviceStoreProvider>,
  );
};

jest.setTimeout(35000);

describe('<DeliveryAddresses/>', () => {
  beforeEach(() => {
    server.use(getProductPricingHandler(), getPincodeInfoHandler({ type: 'delivery_available' }));
  });
  test('should render delivery addresses on screen also from local storage', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Test Name First')).toBeVisible();
    expect(screen.getByText('Test Address First, Test City, Karnataka-560034')).toBeVisible();
    const firstAddressRadio = within(screen.getByTestId('delivery-address-0')).getByRole('radio');
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'true');
  });

  test('should render edit address form if clicked on Edit ', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    const firstAddressRadio = within(screen.getByTestId('delivery-address-0')).getByRole('radio');
    const secondAddressEl = screen.getByTestId('delivery-address-1');
    const secondAddressRadio = within(screen.getByTestId('delivery-address-1')).getByRole('radio');

    await userEvent.click(screen.getByText('Delivery Address'));
    await userEvent.click(within(secondAddressEl).getByText('Edit'));
    await userEvent.type(screen.getByPlaceholderText('Enter Name'), ' New Name');
    await userEvent.click(screen.getByText('Save Address'));

    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'false');
    expect(secondAddressRadio).toHaveAttribute('aria-checked', 'true');

    await waitFor(() => {
      expect(screen.getByText('Test Name Second New Name')).toBeInTheDocument();
    });
  });

  test('should toggle addresses if clicked on radio button ', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    const firstAddressRadio = within(screen.getByTestId('delivery-address-0')).getByRole('radio');
    const secondAddressRadio = within(screen.getByTestId('delivery-address-1')).getByRole('radio');
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'true');
    await userEvent.click(secondAddressRadio);
    expect(secondAddressRadio).toHaveAttribute('aria-checked', 'true');
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'false');
  });

  test('should show add new address form if clicked on add new address btn', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByText('Add New Address')).toBeVisible();
  });

  test.skip('should add new address form if add new address form submitted with correct details', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    await userEvent.click(screen.getByText('Add New Address'));

    await userEvent.click(screen.getByRole('textbox', { name: /Full Name/ }));
    await userEvent.paste('Test Name Third');

    await userEvent.click(screen.getByRole('textbox', { name: /Mobile Number/ }));
    await userEvent.paste('7578967304');

    await userEvent.click(screen.getByRole('textbox', { name: /Pincode/ }));
    await userEvent.paste('560034');

    await userEvent.click(screen.getByRole('textbox', { name: /City/ }));
    await userEvent.paste('Bengaluru');

    await userEvent.click(screen.getByRole('textbox', { name: /Address/ }));
    await userEvent.paste('Test Address Third');

    const stateDropdown = screen.getByPlaceholderText('Select a state');
    await userEvent.click(stateDropdown);
    await userEvent.click(screen.getByTestId('Karnataka-option'));
    await userEvent.click(screen.getByText('Save Address'));

    const thirdAddressRadio = within(screen.getByTestId('delivery-address-2')).getByRole('radio');
    expect(thirdAddressRadio).toHaveAttribute('aria-checked', 'true');

    await waitFor(() => {
      expect(screen.getByText(/Test Name Third, Test Address Third/));
    });
  });

  test('should close new address form if clicked on cancel', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));

    const firstAddressRadio = within(screen.getByTestId('delivery-address-0')).getByRole('radio');
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'true');
    await userEvent.click(screen.getByText('Add New Address'));
    const thirdAddressRadio = within(screen.getByTestId('delivery-address-2')).getByRole('radio');
    expect(thirdAddressRadio).toHaveAttribute('aria-checked', 'true');
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'false');

    await userEvent.click(screen.getByText('Cancel'));
    expect(firstAddressRadio).toHaveAttribute('aria-checked', 'true');
  });

  test('should show selected delivery address in the header', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('header-address')).toHaveTextContent(
      'Test Name First, Test Address First, Test City, Karnataka-560034',
    );

    await userEvent.click(screen.getByText('Add New Address'));
    expect(screen.queryByTestId('header-address')).not.toBeInTheDocument();
  });

  test('should not show selected delivery address in the header if edit mode', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    expect(screen.getByTestId('header-address')).toHaveTextContent(
      'Test Name First, Test Address First, Test City, Karnataka-560034',
    );

    await userEvent.click(screen.getAllByText('Edit')[0]);
    expect(screen.queryByTestId('header-address')).not.toBeInTheDocument();
  });

  test('should close edit form upon clicking on cancel', async () => {
    renderApp();
    await waitForElementToBeRemoved(screen.getByLabelText('pos-store-spinner'));
    await userEvent.click(screen.getAllByText('Edit')[0]);
    await userEvent.click(screen.getAllByText('Cancel')[0]);
    expect(screen.queryAllByText('Cancel').length).toBe(0);
  });
});
