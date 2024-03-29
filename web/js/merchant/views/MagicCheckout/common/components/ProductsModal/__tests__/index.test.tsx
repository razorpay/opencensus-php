import React from 'react';
import '@testing-library/jest-dom/extend-expect';
import { BladeProvider } from '@razorpay/blade/components';
import { bladeTheme } from '@razorpay/blade/tokens';
import { Provider } from 'react-redux';
import * as ModalActions from 'merchant_common/reducers/modals';

import { render, screen, userEvent, waitFor } from 'common/services/test/test-utils';
import { storeWithInitialState } from 'merchant/store';
import { INITIAL_STATE } from 'merchant/views/MagicCheckout/ShippingSettings/__tests__/mocks/fixtures';

import ProductsModal from '..';

jest.mock('merchant/views/MagicCheckout/common/components/InfiniteScroll', () => (props) => {
  const { setHasErrorInFetchingProducts } = props;
  return (
    <button type="button" onClick={() => setHasErrorInFetchingProducts(true)}>
      Product screen
    </button>
  );
});

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

const openModalSpy = jest.spyOn(ModalActions, 'openModal');

const renderProductsModal = (newProps = {}, customState = {}) => {
  return render(
    <Provider store={storeWithInitialState({ ...INITIAL_STATE, ...customState })}>
      <BladeProvider themeTokens={bladeTheme}>
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
  beforeEach(() => {
    openModalSpy.mockClear();
  });

  test('Should render modal', () => {
    renderProductsModal();
    const name = screen.queryByText(/Category name/i);
    const select = screen.queryByText('Select products');
    expect(name).toBeInTheDocument();
    expect(select).toBeInTheDocument();
  });

  test('should open creds modal if unable to fetch products', async () => {
    const customState = {
      ...INITIAL_STATE,
      magic_settings: {
        ...INITIAL_STATE.magic_settings,
        platform: 'woocommerce',
      },
    };
    renderProductsModal({}, customState);

    await userEvent.click(
      screen.getByRole('button', {
        name: 'Product screen',
      }),
    );

    await waitFor(() => {
      expect(openModalSpy).toHaveBeenCalled();
    });
  });
});
