import React, { FunctionComponent } from 'react';

// ui imports
import AddCollectionModal from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddCollectionModal';
import AddProductsModal from 'merchant/views/MagicCheckout/CouponEngine/components/createcoupon/AddProductCollection/AddProductsModal';

interface DiscountedItemModalProps {
  modalType: 'products' | 'collections';
  handleDiscountedItems: (value: any) => void;
  widgetName: string;
  couponName: string;
  discountedItems: Array<object>;
}

const DiscountedItemModal: FunctionComponent<DiscountedItemModalProps> = ({
  modalType,
  handleDiscountedItems,
  widgetName,
  couponName,
  discountedItems,
}) => {
  if (modalType === 'products') {
    return (
      <AddProductsModal
        handleDiscountedItems={handleDiscountedItems}
        widgetName={widgetName}
        couponName={couponName}
        discountedItems={discountedItems}
      />
    );
  }

  return (
    <AddCollectionModal
      handleDiscountedItems={handleDiscountedItems}
      widgetName={widgetName}
      couponName={couponName}
      discountedItems={discountedItems}
    />
  );
};

export default DiscountedItemModal;
