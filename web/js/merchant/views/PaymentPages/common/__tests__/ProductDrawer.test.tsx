import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import ProductDrawer from 'merchant/views/PaymentPages/common/Products/ProductDrawer';
import { paymentPagesErrorHandlers } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/handlers';
import { product } from 'merchant/views/PaymentPages/PaymentPages/__test__/mocks/fixtures/storefront';
import { PRODUCT_MESSAGES } from 'merchant/views/PaymentPages/common/Products/constants';

const mockCloseFunction = jest.fn();
const mockSuccessFunction = jest.fn();

describe('ProductDrawer', () => {
  describe('ProductDrawer -> Add', () => {
    const renderApp = () =>
      render(<ProductDrawer handleClose={mockCloseFunction} onSuccess={mockSuccessFunction} />);
    beforeEach(() => {
      renderApp();
      expect(screen.getByText('Add new product')).toBeInTheDocument();
    });
    test('close button', async () => {
      const closeButton = document.querySelector('.Modal-close');
      if (closeButton) await userEvent.click(closeButton);
      expect(mockCloseFunction).toHaveBeenCalled();
    });

    test('should create product successfully', async () => {
      const product_name = document.querySelector('input[name=product_name]');
      const amount = document.querySelector('input[name=amount]');
      const discounted_amount = document.querySelector('input[name=discounted_amount]');
      const units = document.querySelector('input[name=units]');
      const description = document.querySelector('input[name=description]');
      expect(product_name).toBeInTheDocument();

      if (product_name) await userEvent.type(product_name, product.product_name);
      if (amount) await userEvent.type(amount, String(product.amount));
      if (discounted_amount)
        await userEvent.type(discounted_amount, String(product.discounted_amount));
      if (units) await userEvent.type(units, String(product.units));
      if (description) await userEvent.type(description, product.description);

      const createButton = screen.getByRole('button', { name: /Add product/ });
      if (createButton) await userEvent.click(createButton);
      await waitFor(() => {
        expect(screen.getByText(PRODUCT_MESSAGES.ADD)).toBeInTheDocument();
      });

      expect(mockSuccessFunction).toHaveBeenCalled();
    });

    test('should handle product creation failure', async () => {
      server.use(paymentPagesErrorHandlers.storefrontCatalogCreate());

      const product_name = document.querySelector('input[name=product_name]');
      const amount = document.querySelector('input[name=amount]');
      const discounted_amount = document.querySelector('input[name=discounted_amount]');
      const units = document.querySelector('input[name=units]');
      const description = document.querySelector('input[name=description]');
      expect(product_name).toBeInTheDocument();

      if (product_name) await userEvent.type(product_name, 'Product 1');
      if (amount) await userEvent.type(amount, '15000');
      if (discounted_amount) await userEvent.type(discounted_amount, '10000');
      if (units) await userEvent.type(units, '50');
      if (description) await userEvent.type(description, 'my description');

      const createButton = screen.getByRole('button', { name: /Add product/ });
      if (createButton) await userEvent.click(createButton);
      await waitFor(() => {
        expect(screen.getByText('invalid request sent')).toBeInTheDocument();
      });
      expect(mockCloseFunction).not.toHaveBeenCalled();
    });
  });
  describe('ProductDrawer -> Edit', () => {
    const renderApp = () =>
      render(
        <ProductDrawer
          handleClose={mockCloseFunction}
          productData={product}
          onSuccess={mockSuccessFunction}
        />,
      );
    beforeEach(() => {
      renderApp();
      expect(screen.getByText('Edit product')).toBeInTheDocument();
    });
    test('should edit product successfully', async () => {
      const saveButton = screen.getByRole('button', { name: /Save product details/ });
      if (saveButton) await userEvent.click(saveButton);
      await waitFor(() => {
        expect(screen.getByText(PRODUCT_MESSAGES.UPDATE)).toBeInTheDocument();
      });
      expect(mockSuccessFunction).toHaveBeenCalled();
    });
  });
});
