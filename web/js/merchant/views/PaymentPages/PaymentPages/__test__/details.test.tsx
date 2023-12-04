import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { fireEvent } from '@testing-library/react';
import {
  render,
  screen,
  server,
  userEvent,
  waitFor,
  waitForLoadingToFinish,
  within,
} from 'test-utils';

import { getURLQueryParams } from 'common/utils/rzp-utils';
import store from 'merchant/store';
import { PAYMENT_PAGES_TYPES } from 'merchant/views/PaymentPages/PaymentPages/CreateEdit';
import PaymentPagesDetails from 'merchant/views/PaymentPages/PaymentPages/Details';

// import * as analytics from 'common/utils/analytics';
import 'jest-location-mock';
import { transformedStore } from './mocks/fixtures/storefront';
import { paymentPagesErrorHandlers } from './mocks/handlers';

const globalState = store.getState();

jest.mock('common/utils/analytics');
jest.setTimeout(15000);

const getURL = (id = 'pl_LpoFCooJAk0a2j') => {
  return `/paymentpages/batchpaymentpages/${id}/payments#batchpaymentpages`;
};

const renderApp = (isStorefrontPage = true, isRazorx = true) =>
  render(<PaymentPagesDetails isStorefrontPage={isStorefrontPage} />, {
    initialState: {
      ...globalState,
      session: {
        ...globalState.session,
        user: {
          ...globalState,
          isNoExpiryMandatoryPP: true,
          isPaymentPageStorefrontEnabled: isRazorx,
          isAllowedEdit: jest.fn(() => true),
          merchant: {
            country_code: 'IN',
          },
        },
      },
    },
    renderOptions: {
      initialEntries: ['/paymentpages/storefront/st_L8I1SdFL0YqcMN/payments#paymentpages'],
      path: '/paymentpages/storefront/st_L8I1SdFL0YqcMN/payments#paymentpages',
    },
  });

describe('Payment Pages -> Details page (storefront)', () => {
  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      component: jest.fn(),
      paymentPages: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
  });

  test('manual activation/deactivation of storefront', async () => {
    const { container } = renderApp();
    let panelBody: HTMLElement | null = null;
    let confirmButton;
    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
      expect(screen.getByText('Page Status')).toBeInTheDocument();
      expect(screen.getByText('Created by')).toBeInTheDocument();
    });
    const activateButton = screen.getByRole('button', { name: 'Activate' });
    await userEvent.click(activateButton);
    expect(screen.getByText('Activate Storefront?')).toBeInTheDocument();
    confirmButton = screen.getByText('Yes, activate');
    await userEvent.click(confirmButton);
    await waitFor(() => {
      expect(screen.getByText(/is now active/i)).toBeInTheDocument();
    });
    panelBody = container.querySelector('.panel-body');
    expect(panelBody).toBeInTheDocument();
    if (panelBody) {
      expect(within(panelBody).getByText('Active')).toBeInTheDocument();
    }

    // deactivate page
    const deactivateButton = screen.getByRole('button', { name: 'Deactivate' });
    await userEvent.click(deactivateButton);
    expect(screen.getByText('Deactivate Storefront?')).toBeInTheDocument();
    confirmButton = screen.getByText('Yes, deactivate');
    await userEvent.click(confirmButton);
    await waitFor(() => {
      expect(screen.getByText(/is now inactive/i)).toBeInTheDocument();
    });
    panelBody = container.querySelector('.panel-body');
    expect(panelBody).toBeInTheDocument();
    if (panelBody) {
      expect(within(panelBody).getByText('Inactive')).toBeInTheDocument();
    }
  });
  test('duplicate page flow - storefront', async () => {
    const { history, container } = renderApp();

    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const duplicateButton = container.querySelector('.i-duplicate');
    expect(duplicateButton).toBeInTheDocument();
    if (duplicateButton) await userEvent.click(duplicateButton);

    expect(history.location.pathname).toEqual('/paymentpages/new');
    const queryParams = getURLQueryParams(history.location.search);
    expect(queryParams.duplicate_id).toMatch(transformedStore.id);
    expect(queryParams.type).toMatch(PAYMENT_PAGES_TYPES.storefront);
  });
  test.skip('duplicate page flow - payment pages', async () => {
    // TODO: Fix this while adding PP tests
    const { history, container } = renderApp(false);

    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const duplicateButton = container.querySelector('.i-duplicate');
    expect(duplicateButton).toBeInTheDocument();
    if (duplicateButton) await userEvent.click(duplicateButton);

    expect(history.location.pathname).toEqual('/paymentpages/new');
    const queryParams = getURLQueryParams(history.location.search);
    expect(queryParams.duplicate_id).toMatch(transformedStore.id);
    expect(queryParams.type).toMatch(PAYMENT_PAGES_TYPES.payment_page);
  });
});

describe.skip('update stock flow', () => {
  test('units sold column prefills correctly based on product stock & status', async () => {
    const product1 = transformedStore.payment_page_items[0]; // in_stock
    const product2 = transformedStore.payment_page_items[1]; // unlimited
    const product3 = transformedStore.payment_page_items[2]; // out_of_stock

    const { container } = renderApp();
    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const firstProductUnits: HTMLElement | null = container.querySelector(
      '.table-container > .table:nth-child(1) .item-details-units',
    );
    const secondProductUnits: HTMLElement | null = container.querySelector(
      '.table-container > .table:nth-child(2) .item-details-units',
    );
    const thirdProductUnits: HTMLElement | null = container.querySelector(
      '.table-container > .table:nth-child(3) .item-details-units',
    );
    expect(firstProductUnits).toBeInTheDocument();
    expect(secondProductUnits).toBeInTheDocument();
    expect(thirdProductUnits).toBeInTheDocument();

    if (firstProductUnits) {
      expect(
        within(firstProductUnits).getByText(
          `${product1.quantity_sold} of ${product1.quantity_sold + product1.stock}`,
        ),
      );
      // open the edit flow
      await userEvent.click(within(firstProductUnits).getByText('Update Stock'));
      const noLimitCheckbox = within(firstProductUnits).getByRole('checkbox');
      expect(noLimitCheckbox).not.toBeChecked();
    }

    if (secondProductUnits) {
      expect(within(secondProductUnits).getByText(`${product2.quantity_sold}`));
      // open the edit flow
      await userEvent.click(within(secondProductUnits).getByText('Update Stock'));
      const noLimitCheckbox = within(secondProductUnits).getByRole('checkbox');
      expect(noLimitCheckbox).toBeChecked();
    }

    if (thirdProductUnits) {
      expect(
        within(thirdProductUnits).getByText(
          `${product3.quantity_sold} of ${product3.quantity_sold + product3.stock}`,
        ),
      );
      // open the edit flow
      await userEvent.click(within(thirdProductUnits).getByText('Update Stock'));
      const noLimitCheckbox = within(thirdProductUnits).getByRole('checkbox');
      expect(noLimitCheckbox).not.toBeChecked();
    }
  });
  test('update stock - in_stock to in_stock', async () => {
    const stockInput = '5';
    const product1 = transformedStore.payment_page_items[0];
    server.use(paymentPagesErrorHandlers.storefrontCatalogEditStock());
    const { container } = renderApp();

    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const firstProductUnits = container.querySelector(
      '.table-container > .table:first-child .item-details-units',
    ) as HTMLElement;
    expect(firstProductUnits).toBeInTheDocument();
    await userEvent.click(within(firstProductUnits).getByText('Update Stock'));
    waitFor(() => {
      expect(within(firstProductUnits).queryByText('No Limit')).toBeInTheDocument();
    });
    const noLimitCheckbox = within(firstProductUnits).getByRole('checkbox');
    const updateStockInput = within(firstProductUnits).getByPlaceholderText('Total Stock');
    const updateStockButton = within(firstProductUnits).getByText('Save');
    expect(noLimitCheckbox).not.toBeChecked();

    await userEvent.clear(updateStockInput);
    await userEvent.type(updateStockInput, stockInput);
    // await fireEvent.change(updateStockInput, { target: { value: stockInput } });
    await waitFor(() => {
      expect(updateStockInput).toHaveValue(stockInput);
    });
    await userEvent.click(updateStockButton);
    await waitFor(() => {
      expect(screen.getByText(/Stock updated successfully/i)).toBeInTheDocument();
    });
    if (firstProductUnits)
      expect(
        within(firstProductUnits).getByText(
          `${product1.quantity_sold} of ${product1.quantity_sold + Number(stockInput)}`,
        ),
      ).toBeInTheDocument();
  });
  test('update stock - in_stock to out_of_stock', async () => {
    const stockInput = '0';
    const product1 = transformedStore.payment_page_items[0];
    server.use(paymentPagesErrorHandlers.storefrontCatalogEditStock());
    const { container } = renderApp();

    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const firstProductUnits = container.querySelector(
      '.table-container > .table:first-child .item-details-units',
    ) as HTMLElement;
    expect(firstProductUnits).toBeInTheDocument();
    await userEvent.click(within(firstProductUnits).getByText('Update Stock'));
    waitFor(() => {
      expect(within(firstProductUnits).queryByText('No Limit')).toBeInTheDocument();
    });
    const updateStockInput = within(firstProductUnits).getByPlaceholderText('Total Stock');
    const updateStockButton = within(firstProductUnits).getByText('Save');
    const noLimitCheckbox = within(firstProductUnits).getByRole('checkbox');
    expect(noLimitCheckbox).not.toBeChecked();

    await userEvent.clear(updateStockInput);
    fireEvent.change(updateStockInput, { target: { value: stockInput } });
    expect(updateStockInput).toHaveValue(stockInput);
    await waitFor(() => expect(updateStockInput).toHaveValue(stockInput));
    await userEvent.click(updateStockButton);
    await waitFor(() => {
      expect(screen.getByText(/Stock updated successfully/i)).toBeInTheDocument();
    });
    if (firstProductUnits)
      expect(
        within(firstProductUnits).getByText(
          `${product1.quantity_sold} of ${product1.quantity_sold + Number(stockInput)}`,
        ),
      ).toBeInTheDocument();
  });
  test('update stock - in_stock to unlimited', async () => {
    const product1 = transformedStore.payment_page_items[0];
    server.use(paymentPagesErrorHandlers.storefrontCatalogEditStock());
    const { container } = renderApp();

    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Page URL')).toBeInTheDocument();
    });

    const firstProductUnits = container.querySelector(
      '.table-container > .table:first-child .item-details-units',
    ) as HTMLElement;
    expect(firstProductUnits).toBeInTheDocument();
    await userEvent.click(within(firstProductUnits).getByText('Update Stock'));
    const updateStockButton = within(firstProductUnits).getByText('Save');
    const noLimitCheckbox = within(firstProductUnits).getByRole('checkbox');
    expect(noLimitCheckbox).not.toBeChecked();

    await userEvent.click(noLimitCheckbox);
    expect(noLimitCheckbox).toBeChecked();
    await userEvent.click(updateStockButton);
    await waitFor(() => {
      expect(screen.getByText(/Stock updated successfully/i)).toBeInTheDocument();
    });
    if (firstProductUnits)
      expect(within(firstProductUnits).getByText(`${product1.quantity_sold}`)).toBeInTheDocument();
  });
});

describe('Batch Payment Pages -> Details page', () => {
  const id = 'pl_LpoFCooJAk0a2j';
  const defaultProps = {
    match: {
      params: {
        id,
      },
    },
  };
  const renderApp = (props = {}, plId = id) =>
    render(<PaymentPagesDetails {...defaultProps} {...props} />, {
      initialState: {
        ...globalState,
        session: {
          ...globalState.session,
          user: {
            ...globalState,
            isNoExpiryMandatoryPP: true,
            isAllowedEdit: jest.fn(() => true),
            merchant: {
              country_code: 'IN',
            },
          },
        },
      },
      renderOptions: {
        initialEntries: [getURL(plId)],
        path: getURL(plId),
      },
    });

  beforeAll(() => {
    window.rzp_user = {};
    window.rzpQ = {
      component: jest.fn(),
      paymentPages: () => ({
        success: jest.fn(),
        interaction: jest.fn(),
      }),
    };
  });

  test('should render payment page details page', async () => {
    const defaultProps = {
      id: 'pl_validid',
      isBatchPaymentPages: false,
    };
    renderApp(defaultProps, 'pl_validid');
    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('Share Page')).toBeInTheDocument();
    });
    expect(screen.getByText('Page URL')).toBeInTheDocument();
    expect(screen.getByText('Page Status')).toBeInTheDocument();
    expect(screen.getByText('Total Payments')).toBeInTheDocument();
    expect(screen.getByText('Total revenue')).toBeInTheDocument();
  });

  test('should render batch payment page details page', async () => {
    const defaultProps = {
      id: 'pl_validid',
      isBatchPaymentPages: true,
    };
    renderApp(defaultProps, 'pl_validid');
    await waitForLoadingToFinish();
    await waitFor(() => {
      expect(screen.getByText('View Batch Details')).toBeInTheDocument();
    });
    expect(screen.getByText('Page URL')).toBeInTheDocument();
    expect(screen.getByText('Page Status')).toBeInTheDocument();
    expect(screen.getByText('Paid Count')).toBeInTheDocument();
    expect(screen.getByText('Paid Amount')).toBeInTheDocument();
    expect(screen.getByText('Unpaid Count')).toBeInTheDocument();
    expect(screen.getByText('Unpaid Amount')).toBeInTheDocument();
  });

  test('should show error while getting pending payments', async () => {
    const defaultProps = {
      id: 'pl_invalidid',
      isBatchPaymentPages: true,
    };
    server.use(
      paymentPagesErrorHandlers.paymentPagesDetailsError(),
      paymentPagesErrorHandlers.paymentsPendingError(),
    );
    renderApp(defaultProps, 'pl_invalidid');
    await waitForLoadingToFinish();
    expect(screen.getByText(/No results found for id/i)).toBeInTheDocument();
    await waitFor(() => {
      expect(
        screen.getByText(/The requested URL was not found on the server/i),
      ).toBeInTheDocument();
    });
  });
});
