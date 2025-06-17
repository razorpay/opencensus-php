import React from 'react';
import { Box, IconButton, IconComponent, Text } from '@razorpay/blade/components';

import OfferStrip from 'apps/pos/src/app/views/SelfServe/Catalog/OfferStrip/index';

type OfferStrip = {
  text: string;
  type?: 'light' | 'dark';
  variant?: 'offer' | 'info';
  isPartnerPricing?: boolean;
};

interface CartHeaderProps {
  productTitle: string;
  icon?: IconComponent;
  iconClickHandler?: () => void;
  cartImage: string;
  offerStrip?: OfferStrip | null;
}
const CartHeader = ({
  productTitle,
  icon,
  iconClickHandler = () => {},
  cartImage,
  offerStrip,
}: CartHeaderProps) => {
  return (
    <Box display="flex" justifyContent="space-between">
      <Box display="flex" alignItems="center">
        <Box
          backgroundImage={`url("${cartImage}")`}
          backgroundRepeat="no-repeat"
          backgroundSize="cover"
          height={{ base: '65px', l: '70px' }}
          width={{ base: '65px', l: '70px' }}
          backgroundPosition="center center"
          backgroundColor="surface.background.gray.moderate"
          borderRadius="large"
          marginRight="spacing.4"
        />
        <Box>
          <Text weight="semibold" size="large" color="surface.text.gray.subtle">
            {productTitle}
          </Text>

          {offerStrip ? (
            <OfferStrip
              isRounded={true}
              size="small"
              text={offerStrip.text}
              isPartnerPricing={offerStrip.isPartnerPricing}
            />
          ) : null}
        </Box>
      </Box>
      {icon ? (
        <Box paddingTop="spacing.5">
          <IconButton
            icon={icon}
            size="large"
            onClick={iconClickHandler}
            accessibilityLabel="product delete icon"
          />
        </Box>
      ) : null}
    </Box>
  );
};

export default CartHeader;
