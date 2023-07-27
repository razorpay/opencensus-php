import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import ProductItem from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/CategorySettings/Item';
import InfiniteLoader from 'merchant/views/MagicCheckout/common/components/InfiniteScroll';

import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import { createCategory, updateCategory } from 'merchant/reducers/magicCheckout/codEngine/action';

import { merchantFetch } from 'merchant/utils/ajax';

import { MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import {
  APIPayload,
  Product,
} from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/CategorySettings/types';

const SettingModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODCategorySettings" */ 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingModal'
    ),
);

interface ProductModalProps {
  id?: string;
  item_categories: Record<string, unknown>[];
  mode?: string;
  createCategory: (payload: Record<string, unknown>) => Promise<unknown>;
  updateCategory: (payload: Record<string, unknown>) => Promise<unknown>;
  closeModal: () => void;
  showNotification: (payload: Record<string, unknown>) => void;
}

const ProductsModal = ({
  id,
  item_categories,
  mode,
  createCategory,
  updateCategory,
  closeModal,
  showNotification,
}: ProductModalProps) => {
  const category = item_categories.find((c) => c.id === id);
  const isEditMode = mode === MODAL_MODES.EDIT;
  const MODAL_HEADER = `${isEditMode ? 'Edit' : 'Create'} category`;
  const [searchText, setSearchText] = useState<string>('');
  const [products, setProducts] = useState<Record<string, unknown>[]>([]);

  const handleSearch = (searchText) => {
    setSearchText(searchText);
  };

  const confirmCategory = (categoryName) => {
    if (!products.length) {
      showNotification({
        type: 'error',
        message: 'Select atleast one product to create a category',
      });
    } else {
      const actionFn = isEditMode ? updateCategory : createCategory;
      const payload: APIPayload = {
        name: categoryName,
        items: products as Product[],
      };
      if (category?.id) payload.id = category.id as string;
      actionFn(payload)
        .then(() => {
          showNotification({
            type: 'success',
            message: () => (
              <DisplayNotificationTxt
                notificationTxt={`${
                  isEditMode ? 'Category updated' : 'Category created'
                } successfully`}
              />
            ),
          });
          closeModal();
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err?.errors[0] || 'Something went wrong',
          });
        });
    }
  };

  const handleSelectedProduct = ({ name, id, image_url }: Product, status: boolean) => {
    if (status) {
      setProducts([...products, { product_name: name, product_id: id, image_url }]);
    } else {
      setProducts(products.filter((p) => p.product_id !== id));
    }
  };

  useEffect(() => {
    if (isEditMode) {
      merchantFetch({
        url: `1cc/shipping/cod/item/category/${id}`,
        method: 'get',
      })
        .then(({ data }) => {
          const { items } = data;
          if (items.length) {
            setProducts(items);
          }
        })
        .catch((err) => {
          showNotification({
            type: 'error',
            message: err?.errors?.[0] || 'Something went wrong',
          });
        });
    }
  }, []);

  const getSelectedStatus = (item) => products.some((product) => product.product_id === item.id);

  return (
    <SuspenseWithLoader type="center">
      <SettingModal
        header={MODAL_HEADER}
        variant="Category"
        placeholder="products"
        searchFn={handleSearch}
        name={isEditMode ? category?.name : ''}
        confirmAction={confirmCategory}
        className="cod-config-modal"
        itemClassName="products"
        type="item_categories"
      >
        <InfiniteLoader<Product>
          isCursorBased
          pageSize={250}
          url="1cc/shipping/cod/item/category/search/products"
          rowRenderer={(item) => (
            <ProductItem
              isDisabled={
                (item.internal_id && item.internal_category !== category?.name) as boolean
              }
              handleSelectedProduct={handleSelectedProduct}
              key={item.id}
              item={{ ...item, selected: getSelectedStatus(item) }}
            />
          )}
          queryKey="cod-products"
          itemsKey="products"
          searchText={searchText}
        />
      </SettingModal>
    </SuspenseWithLoader>
  );
};
const mapStateToProps = (state) => ({
  item_categories: state.magicCODEngine.item_categories,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
      showNotification,
      createCategory,
      updateCategory,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(ProductsModal);
