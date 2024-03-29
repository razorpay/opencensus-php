import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, userEvent, waitFor, within } from 'test-utils';
import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';
import SelectProductDrawer from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/SelectProductDrawer';

const mockCloseFunction = jest.fn();
const mockAddFunction = jest.fn();

describe('ProductDrawer', () => {
  const renderApp = () =>
    render(<SelectProductDrawer handleClose={mockCloseFunction} openAddModal={mockAddFunction} />);
  describe('ProductDrawer -> Basic flow', () => {
    test('API success flow', async () => {
      const { container } = renderApp();
      expect(screen.getByText('Add products to page')).toBeInTheDocument();
      expect(screen.getByTestId('select-product-skeleton')).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.queryByTestId('select-product-skeleton')).not.toBeInTheDocument();
      });
      expect(screen.getAllByRole('checkbox')[0]).toBeInTheDocument();

      const closeButton = container.querySelector('.Modal-close') as Element;
      expect(closeButton).toBeInTheDocument();
      await userEvent.click(closeButton);
      expect(mockCloseFunction).toHaveBeenCalled();

      const addProductButton = screen.getByRole('button', {
        name: /Add a new product/,
      });
      expect(addProductButton).toBeInTheDocument();
      await userEvent.click(addProductButton);
      expect(mockAddFunction).toHaveBeenCalled();
    }, 15000);

    test('API failure flow', async () => {
      // set handlers before calling renderApp
      server.use(paymentPagesErrorHandlers.storefrontAllCatalog());
      renderApp();

      expect(screen.getByTestId('select-product-skeleton')).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.queryByTestId('select-product-skeleton')).not.toBeInTheDocument();
      });

      expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
      const checkboxContainer = screen.getByTestId('select-checkbox-container');
      expect(within(checkboxContainer).queryByText(/Something went wrong/)).toBeInTheDocument();
    });
  });
});
