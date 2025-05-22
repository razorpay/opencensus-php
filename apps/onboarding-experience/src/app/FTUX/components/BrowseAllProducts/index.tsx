import React, { useState } from 'react';
import { Box, Button, Text } from '@razorpay/blade/components';
import ProductRecommender, {
  ActiveProductRecommenderScreen,
} from '@FTUX/modals/ProductRecommender';
import { isMobileDevice } from '@libs/shared-utils';
import ChatHelp from 'apps/onboarding-experience/src/assets/ChatHelp.svg';

const BrowseAllProducts = () => {
  const isMobile = isMobileDevice();
  const [isExploreModalOpen, setIsExploreModalOpen] = useState(false);
  return (
    <Box
      display="flex"
      flexDirection={{ base: 'column', m: 'row' }}
      alignItems="center"
      gap="spacing.5"
    >
      <Box display="flex" flexDirection="row" alignItems="center" gap="spacing.5">
        <img width="24px" src={ChatHelp} alt="Help" />
        <Text>Can't find the right product for you? Choose from 10 other no code products</Text>
      </Box>
      <Button
        variant="tertiary"
        onClick={() => {
          setIsExploreModalOpen(true);
        }}
        marginLeft="auto"
        isFullWidth={isMobile ? true : false}
      >
        Browse all products
      </Button>
      {isExploreModalOpen && (
        <ProductRecommender
          activeScreen={ActiveProductRecommenderScreen.ALL_PRODUCTS}
          onDismiss={() => {
            setIsExploreModalOpen(false);
          }}
        />
      )}
    </Box>
  );
};

export default BrowseAllProducts;
