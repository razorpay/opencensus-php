import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import {
  createCategory,
  updateCategory,
} from 'merchant/reducers/magicCheckout/shippingEngine/action';
import { ShippingEngineStore } from 'merchant/reducers/magicCheckout/shippingEngine/types';
import { merchantFetch } from 'merchant/utils/ajax';
import ProductsModal from 'merchant/views/MagicCheckout/common/components/ProductsModal';
import { ItemsCategory } from 'merchant/views/MagicCheckout/common/components/ProductsModal/types';
import { MODAL_MODES } from 'merchant/views/MagicCheckout/common/components/SettingsModal/constants';
import { MAGIC_APP_NAME, SOPC_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

interface ModalProps {
  shippingEngine: ShippingEngineStore;
  isOpen: boolean;
  categoryId?: string;
  closeModal: () => void;
  createCategory: (zone: ItemsCategory) => Promise<void>;
  updateCategory: (zone: ItemsCategory) => Promise<void>;
  isRCOD: string;
}

const Modal = ({
  shippingEngine,
  isOpen,
  categoryId,
  closeModal,
  createCategory,
  updateCategory,
  isRCOD,
}: ModalProps) => {
  const appType = isRCOD ? SOPC_APP_NAME : MAGIC_APP_NAME;

  const queryKey = Boolean(categoryId)
    ? [`magic.shipping-category.${categoryId}`]
    : [`magic.shipping-category`]; //queryKey should have only known properties, hence this check else it will throw ts error
  const { isFetching, data: category } = useQuery({
    queryKey,
    queryFn: (): Promise<ItemsCategory> => {
      return merchantFetch({
        url: `1cc/shipping/item/category/${categoryId}`,
        method: 'get',
      }).then(({ data }) => data);
    },
    refetchOnWindowFocus: false,
    enabled: Boolean(categoryId),
  });
  return (
    <ProductsModal
      isOpen={isOpen}
      closeModal={closeModal}
      category={category}
      createCategory={createCategory}
      updateCategory={updateCategory}
      productsUrl="1cc/shipping/item/category/search/products"
      mode={categoryId ? MODAL_MODES.EDIT : MODAL_MODES.CREATE}
      entityType="shipping"
      loading={shippingEngine.isLoading.item_categories}
      isCategoryFetching={isFetching}
      appType={appType}
    />
  );
};

const mapStateToProps = (state) => ({
  shippingEngine: state.magicShippingEngine,
  isRCOD: state.magicCheckout.rcod,
});

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      createCategory,
      updateCategory,
    },
    dispatch,
  );

export default connect(mapStateToProps, mapDispatchToProps)(Modal);
