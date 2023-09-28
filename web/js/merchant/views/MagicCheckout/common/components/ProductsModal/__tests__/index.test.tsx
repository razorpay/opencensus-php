import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { paymentTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';

import { render, screen } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { INITIAL_STATE } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

import ProductsModal from '..';

const createCategory = jest.fn();
const updateCategory = jest.fn();

const observe = jest.fn();
const unobserve = jest.fn();

// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore
window.IntersectionObserver = jest.fn(() => ({
  observe,
  unobserve,
}));

const renderProductsModal = (newProps = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE })}>
      <BladeProvider themeTokens={paymentTheme}>
        <ProductsModal
          isOpen={true}
          closeModal={jest.fn()}
          createCategory={createCategory}
          updateCategory={updateCategory}
          productsUrl="1cc/shipping/item/category/search/products"
          mode="create"
          entityType="shipping"
          loading={false}
          isCategoryFetching={false}
          {...newProps}
        />
      </BladeProvider>
    </Provider>,
  );
};

describe('Product Modal', () => {
  test('Should render modal', () => {
    renderProductsModal();
    const name = screen.queryByText(/Category name/i);
    const select = screen.queryByText('Select products');
    expect(name).toBeInTheDocument();
    expect(select).toBeInTheDocument();
  });
});
