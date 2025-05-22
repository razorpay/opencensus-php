import React, { useState } from 'react';
import { Box } from '@razorpay/blade/components';
import ProductCategories from './ProductCategories';
import ProductRecommendations from './ProductRecommendations';
import ExploreAllProducts from './ExploreAllProducts';
import { PRODUCT_CATEGORIES } from '@FTUX/constants/products';

export enum ActiveProductRecommenderScreen {
  CATEGORIES = 'categories',
  RECOMMENDATIONS = 'recommendations',
  ALL_PRODUCTS = 'allProducts',
}

function ProductRecommender({
  onDismiss,
  activeScreen = ActiveProductRecommenderScreen.CATEGORIES,
}: {
  onDismiss: () => void;
  activeScreen?: ActiveProductRecommenderScreen;
}) {
  // State for tracking which modal is visible
  const [currentView, setCurrentView] = useState<ActiveProductRecommenderScreen>(activeScreen);
  const [selectedCategoryIndex, setSelectedCategoryIndex] = useState<number | null>(null);

  // Handle selecting a category
  const handleCategorySelection = (categoryIndex: number) => {
    setSelectedCategoryIndex(categoryIndex);
    setCurrentView(ActiveProductRecommenderScreen.RECOMMENDATIONS);
  };

  // Handle going back to categories
  const handleMoveToCategories = () => setCurrentView(ActiveProductRecommenderScreen.CATEGORIES);

  // Handle showing explore all products
  const handleMoveToAllProducts = () => setCurrentView(ActiveProductRecommenderScreen.ALL_PRODUCTS);

  return (
    <Box>
      {currentView === ActiveProductRecommenderScreen.CATEGORIES && (
        <ProductCategories onDismiss={onDismiss} makeSelection={handleCategorySelection} />
      )}

      {currentView === ActiveProductRecommenderScreen.RECOMMENDATIONS &&
        selectedCategoryIndex !== null && (
          <ProductRecommendations
            onDismiss={onDismiss}
            productsList={PRODUCT_CATEGORIES[selectedCategoryIndex].products}
            onBackClick={handleMoveToCategories}
            onExploreAllProducts={handleMoveToAllProducts}
          />
        )}

      {currentView === ActiveProductRecommenderScreen.ALL_PRODUCTS && (
        <ExploreAllProducts onDismiss={onDismiss} handleMoveToCategories={handleMoveToCategories} />
      )}
    </Box>
  );
}

export default ProductRecommender;
