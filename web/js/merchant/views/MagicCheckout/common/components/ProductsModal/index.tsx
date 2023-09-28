import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import Spinner from 'common/ui/Spinner';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import ProductItem from './Item';
import InfiniteLoader from 'merchant/views/MagicCheckout/common/components/InfiniteScroll';
import SettingsModal from 'merchant/views/MagicCheckout/common/components/SettingsModal';

import { showNotification } from 'merchant_common/reducers/notifications';

import { MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { ItemsCategory, Product } from './types';

interface ProductModalProps {
  entityType: 'cod' | 'shipping';
  category?: ItemsCategory;
  isOpen: boolean;
  loading: boolean;
  isCategoryFetching: boolean;
  mode?: string;
  productsUrl: string;
  createCategory: (payload: ItemsCategory) => Promise<void>;
  updateCategory: (payload: ItemsCategory) => Promise<void>;
  closeModal: () => void;
  showNotification: (payload: Record<string, unknown>) => void;
}

const ProductsModal = ({
  isOpen,
  loading,
  entityType,
  category,
  mode,
  createCategory,
  updateCategory,
  isCategoryFetching,
  closeModal,
  showNotification,
  productsUrl,
}: ProductModalProps) => {
  const selectedCategory = category;
  const isEditMode = mode === MODAL_MODES.EDIT;
  const MODAL_HEADER = `${isEditMode ? 'Edit' : 'Create'} category`;
  const [searchText, setSearchText] = useState<string>('');
  const [products, setProducts] = useState<Record<string, unknown>[]>([]);

  useEffect(() => {
    if (selectedCategory?.items) {
      setProducts(selectedCategory.items);
    }
  }, [selectedCategory]);

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
      const payload: ItemsCategory = {
        name: categoryName,
        type: entityType,
        items: products as Product[],
      };
      if (selectedCategory?.id) payload.id = selectedCategory.id as string;
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
      setProducts([...products, { name, reference_id: id, image_url, reference_type: 'product' }]);
    } else {
      setProducts(products.filter((p) => p.reference_id !== id));
    }
  };

  const getSelectedStatus = (item) => {
    return products.some((product) => product.reference_id === item.id);
  };

  return (
    <SettingsModal
      header={MODAL_HEADER}
      variant="category"
      searchPlaceholder="products"
      searchFn={handleSearch}
      entityName={isEditMode ? (category?.name as string) : ''}
      confirmAction={confirmCategory}
      itemClassName="products"
      isOpen={isOpen}
      isLoading={loading}
      handleDismiss={closeModal}
      disableConfirmButton={loading}
    >
      {isCategoryFetching ? (
        <div className="page-spinner-container">
          <Spinner />
        </div>
      ) : (
        <InfiniteLoader<Product>
          isCursorBased
          pageSize={250}
          url={productsUrl}
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
      )}
    </SettingsModal>
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      showNotification,
    },
    dispatch,
  );

const Component: ({
  isOpen,
  category,
  entityType,
  mode,
  createCategory,
  updateCategory,
  closeModal,
  isCategoryFetching,
  loading,
  productsUrl,
}: Omit<ProductModalProps, 'showNotification'>) => JSX.Element = connect(
  null,
  mapDispatchToProps,
)(ProductsModal);
export default Component;
