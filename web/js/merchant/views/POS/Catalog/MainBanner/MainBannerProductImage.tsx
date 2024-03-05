import React from 'react';
import { Amount, Box } from '@razorpay/blade/components';

import MainBannerAndroidSmartPosImg from 'assets/pos/main-banner/androidpos.webp';
import PricingTagImage from 'assets/pos/main-banner/pricetag.webp';
import AmountWithStrikeThrough from 'merchant/views/POS/ProductDescription/ProductPriceCards/AmountWithStrikeThrough';

import { StyledPriceTagImage, StyledMainBannerImage } from './styles';

type MainBannerProductImageProps = {
  price: number;
  prevPrice?: number;
};

const PriceTag = ({ price, prevPrice }: MainBannerProductImageProps): JSX.Element => {
  return (
    <Box
      position="absolute"
      zIndex={1}
      bottom="35px"
      width={prevPrice ? '150px' : '130px'}
      minWidth="129px"
      right={{ base: prevPrice ? '130px' : '140px', m: '160px' }}
      display="flex"
      justifyContent="center"
      alignItems="center"
    >
      <Box
        position="absolute"
        display="flex"
        justifyContent="center"
        alignItems="center"
        flexDirection="column"
        width="100%"
        marginBottom="spacing.3"
      >
        <Amount
          value={price}
          isAffixSubtle={false}
          suffix="none"
          size="heading-small-bold"
          testID="price-tag-amount"
        />
        {prevPrice ? (
          <AmountWithStrikeThrough
            value={prevPrice}
            size="heading-small-bold"
            testID="price-tag-prev-amount"
          />
        ) : null}
      </Box>
      <StyledPriceTagImage src={PricingTagImage} alt="product price tag" />
    </Box>
  );
};

const MainBannerProductImage = ({ price, prevPrice }: MainBannerProductImageProps): JSX.Element => {
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
        <PriceTag price={price} prevPrice={prevPrice} />
        <StyledMainBannerImage src={MainBannerAndroidSmartPosImg} alt="android pos image" />
      </Box>
    </Box>
  );
};

export default MainBannerProductImage;
