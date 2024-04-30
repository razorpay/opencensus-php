import React, { useEffect, useState } from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import lazy from 'merchant/routes/LazyLoader';
import SuspenseWithLoader from 'common/new-ui/SuspenseWithLoader';

import Spinner from 'common/ui/Spinner';
import { DisplayNotificationTxt } from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import ProductItem from './Item';
import InfiniteLoader from 'merchant/views/MagicCheckout/common/components/InfiniteScroll';
import SettingsModal from 'merchant/views/MagicCheckout/common/components/SettingsModal';

import { showNotification } from 'merchant_common/reducers/notifications';

import { MODAL_MODES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { ItemsCategory, Product } from './types';
import { updateMagicSettings } from 'merchant/reducers/magicCheckout/magicSettings/actions';
import { openModal } from 'merchant_common/reducers/modals';

import { PLATFORMS } from 'merchant/views/MagicCheckout/MagicSettings/constants';

const CredentialsModal = lazy(
  () =>
    import(
      /* webpackChunkName: "MagicCODCategorySettings" */ 'merchant/views/MagicCheckout/MagicSettings/manualReviewSettings/Woocommerce'
    ),
);
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
  platform: string;
  updateSettings: (payload: Record<string, any>, isLoading: boolean) => any;
  openModal: (arg: Record<string, any>) => any;
  appType?: string;
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
  platform,
  updateSettings,
  openModal,
  appType,
}: ProductModalProps) => {
  const selectedCategory = category;
  const isEditMode = mode === MODAL_MODES.EDIT;
  const MODAL_HEADER = `${isEditMode ? 'Edit' : 'Create'} category`;
  const [searchText, setSearchText] = useState<string>('');
  const [products, setProducts] = useState<Record<string, unknown>[]>([]);
  const [hasErrorInFetchingProducts, setHasErrorInFetchingProducts] = useState(false);

  useEffect(() => {
    if (selectedCategory?.items) {
      setProducts(selectedCategory.items);
    }
  }, [selectedCategory]);

  const showAlertNotification = () => {
    setHasErrorInFetchingProducts(false);
    showNotification({
      type: 'neutral',
      message: 'Product categories can not be created. Please provide API credentials',
      closeTimeout: 10000,
      className: 'magic-notification',
    });
    closeModal();
  };

  const updateConfiguration = (payload: Record<string, any>) => {
    const params = {
      platform,
      ...payload,
    };
    updateSettings(params, false).then(() => {
      setHasErrorInFetchingProducts(false);
      showNotification({
        type: 'success',
        message: 'Credentials saved successfully.',
      });
      closeModal();
    });
  };

  const openCredsModal = () => {
    openModal({
      size: 'large',
      className: `woocommerceManualSettingModal`,
      component: (
        <SuspenseWithLoader type="center">
          <CredentialsModal
            platform={PLATFORMS.VALUES.WOOCOMMERCE}
            submitCredentials={(payload: Record<string, any>) => updateConfiguration(payload)}
            modalDesc="Magic checkout needs your Woocommerce credentials to create product categories."
            customCloseModal={showAlertNotification}
          />
        </SuspenseWithLoader>
      ),
    });
  };

  useEffect(() => {
    if (hasErrorInFetchingProducts && platform === PLATFORMS.VALUES.WOOCOMMERCE) {
      openCredsModal();
    }
  }, [hasErrorInFetchingProducts]);

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
          <Spinner center />
        </div>
      ) : (
        <InfiniteLoader<Product>
          isCursorBased
          pageSize={100}
          url={productsUrl}
          appType={appType}
          setHasErrorInFetchingProducts={setHasErrorInFetchingProducts}
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
      updateSettings: updateMagicSettings,
      openModal,
    },
    dispatch,
  );

const mapStateToProps = (state) => ({
  platform: state.magic_settings.platform,
});
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
}: Omit<
  ProductModalProps,
  'showNotification' | 'updateSettings' | 'openModal' | 'platform'
>) => JSX.Element = connect(mapStateToProps, mapDispatchToProps)(ProductsModal);
export default Component;
