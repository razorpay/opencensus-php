import React from 'react';
import { Amount, Box } from '@razorpay/blade/components';

import MainBannerAndroidSmartPosImg from 'assets/pos/main-banner/androidpos.webp';
import PricingTagImage from 'assets/pos/main-banner/pricetag.webp';

import { StyledPriceTagImage, StyledMainBannerImage } from './styles';

const PriceTag = ({ value }: { value: number }): JSX.Element => {
  return (
    <Box
      position="absolute"
      zIndex={1}
      bottom="10%"
      width="60%"
      minWidth="112px"
      right={{ base: '140px', m: '160px' }}
    >
      <Box
        position="absolute"
        top="40%"
        display="flex"
        justifyContent="center"
        width="100%"
        left="-4.3px"
      >
        <Amount
          value={value}
          isAffixSubtle={false}
          suffix="none"
          size="heading-small-bold"
          testID="price-tag-amount"
        />
      </Box>
      <StyledPriceTagImage src={PricingTagImage} alt="product price tag" />
    </Box>
  );
};

const MainBannerProductImage = ({ price }: { price: number }): JSX.Element => {
  return (
    <Box
      position="relative"
      height="100%"
      display="flex"
      justifyContent={{ base: 'center', l: 'right' }}
      alignItems="flex-end"
      zIndex={1}
      minWidth="350px"
    >
      <Box
        position={{ base: 'relative', l: 'absolute' }}
        bottom="-25px"
        right={{ base: '0px', l: '10px' }}
        marginRight={{ base: '0px', l: 'spacing.5' }}
      >
        <PriceTag value={price} />
        <StyledMainBannerImage src={MainBannerAndroidSmartPosImg} alt="android pos image" />
      </Box>
    </Box>
  );
};

export default MainBannerProductImage;
