import React, { useEffect } from 'react';
import { useConnectedNavigationStore } from '@federated/apps/shell/connected-navigation/connectedNavigationStore';
import { useGetActiveProduct } from '../Navigation/hooks';
import { useTopNavigationData } from '../Navigation/TopNavigation/hooks';
import { useLocation } from 'react-router-dom';

/**
 * SyncProductStore component
 *
 * This component doesn't render anything but synchronizes the global navigation store
 * with the current active product based on URL path.
 */
export const SyncProductStore: React.FC = () => {
  const { setSelectedProduct } = useConnectedNavigationStore((state) => state);
  const { activeProductAlias } = useGetActiveProduct();
  const { products, isLoading } = useTopNavigationData();
  const location = useLocation();

  const handleProductSelect = () => {
    const selectedProduct = products.find((product) => {
      if (product.alias === activeProductAlias || product.sharedId === activeProductAlias) {
        return true;
      }
      if (product.type === 'more_navigation_item') {
        return product.components.find(
          (moreProduct) =>
            moreProduct.alias === activeProductAlias || moreProduct.sharedId === activeProductAlias,
        );
      }
      return false;
    });

    setSelectedProduct({
      title: selectedProduct?.title,
      alias: selectedProduct?.alias,
      selectAction: selectedProduct?.selectAction,
    });
  };

  useEffect(() => {
    // Set the product in store based on which path is active
    if (!isLoading) {
      handleProductSelect();
    }
  }, [isLoading, setSelectedProduct, location]);

  // This component doesn't render anything
  return null;
};
