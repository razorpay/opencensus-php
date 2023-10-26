import React, { FunctionComponent } from 'react';

// ui imports
import AddCollectionModal from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddCollectionModal';
import AddProductsModal from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductsModal';

interface DiscountedItemModalProps {
  modalType: 'products' | 'collections';
  handleDiscountedItems: (value: any) => void;
}

const DiscountedItemModal: FunctionComponent<DiscountedItemModalProps> = ({
  modalType,
  handleDiscountedItems,
}) => {
  if (modalType === 'products') {
    return <AddProductsModal handleDiscountedItems={handleDiscountedItems} />;
  }

  return <AddCollectionModal handleDiscountedItems={handleDiscountedItems} />;
};

export default DiscountedItemModal;
