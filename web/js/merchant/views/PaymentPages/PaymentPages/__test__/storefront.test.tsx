import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { render, screen, server, userEvent, waitFor } from 'test-utils';
import StoreFront from 'merchant/views/PaymentPages/PaymentPages/CreateEdit/Storefront/index';
import { paymentPagesErrorHandlers } from './mocks/handlers';
import { PRODUCT_MESSAGES } from 'merchant/views/PaymentPages/common/Products/constants';
import { product } from './mocks/fixtures/storefront';

describe('Storefront -> Create', () => {
  beforeAll(() => {
    window.rzp_user = {};
  });

  describe('no products in central catalog', () => {
    const renderApp = () => {
      server.use(paymentPagesErrorHandlers.storefrontAllCatalogEmpty());
      return render(<StoreFront />);
    };

    test('should render the blank state', async () => {
      renderApp();
      expect(screen.getByLabelText('Loading products')).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.queryByLabelText('Loading products')).not.toBeInTheDocument();
      });

      expect(screen.getByText('Add your first product')).toBeInTheDocument();
      expect(screen.getAllByText('Sample Product')[0]).toBeInTheDocument();
      expect(screen.getByText('Contact phone/email')).toBeInTheDocument();
    });

    test('should allow to remove sample products section', async () => {
      const { container } = renderApp();
      expect(screen.getByLabelText('Loading products')).toBeInTheDocument();
      await waitFor(() => {
        expect(screen.queryByLabelText('Loading products')).not.toBeInTheDocument();
      });

      // click sample product's settings icon
      const sampleProductSettingsButton = container.querySelector(
        '.sample-products-section li svg',
      ) as Element;
      await userEvent.click(sampleProductSettingsButton);
      // click remove button
      const removeButton = screen.getByText('Remove');
      expect(removeButton).toBeInTheDocument();
      await userEvent.click(removeButton);
      // sample product is removed
      expect(screen.queryByText('Sample Product')).not.toBeInTheDocument();
    });

    describe('Create -> ProductDrawer', () => {
      // Reference: https://stackoverflow.com/a/55081916/6127580
      // increase timeout for this particular test, as its slow
      test('should add product to storefront via the drawer', async () => {
        const { container } = renderApp();
        expect(screen.getByLabelText('Loading products')).toBeInTheDocument();
        await waitFor(() => {
          expect(screen.queryByLabelText('Loading products')).not.toBeInTheDocument();
        });

        const addProductButton = screen.getByText('Add your first product') as Element;
        expect(addProductButton).toBeInTheDocument();
        await userEvent.click(addProductButton);

        await waitFor(() => {
          expect(
            screen.getByRole('heading', {
              name: 'Add new product',
            }),
          ).toBeInTheDocument();
        });
        const product_name = container.querySelector('input[name=product_name]') as Element;
        const amount = container.querySelector('input[name=amount]') as Element;

        const discounted_amount = container.querySelector(
          'input[name=discounted_amount]',
        ) as Element;
        const units = container.querySelector('input[name=units]') as Element;
        const description = container.querySelector('textarea[name=description]') as Element;
        expect(product_name).toBeInTheDocument();

        await userEvent.type(product_name, product.product_name);
        await userEvent.type(amount, String(product.amount));

        await userEvent.type(discounted_amount, String(product.discounted_amount));
        await userEvent.type(units, String(product.units));
        await userEvent.type(description, product.description);

        const createButton = screen.getAllByRole('button', { name: /Add product/ })[0];
        await userEvent.click(createButton);

        await waitFor(() => {
          expect(screen.getByText(PRODUCT_MESSAGES.ADD)).toBeInTheDocument();
        });
        expect(
          screen.queryByRole('heading', {
            name: 'Add new product',
          }),
        ).not.toBeInTheDocument();
        expect(screen.getByText(product.product_name)).toBeInTheDocument();
      }, 15000);
    });
  });

  //TODO: add tests later
  describe('products exist in central catalog', () => {});
});
