import React from 'react';
import { Amount, Box } from '@razorpay/blade/components';

import PartnerPosPriceTagImage from 'assets/partner-dashboard/PartnerPosPriceTag.svg';
import MainBannerAndroidSmartPosImg from 'assets/pos/main-banner/androidpos.webp';
import PricingTagImage from 'assets/pos/main-banner/pricetag.webp';

import { StyledPriceTagImage, StyledMainBannerImage } from './styles';

type MainBannerProductImageProps = {
  price: number;
  prevPrice?: number;
  isPartnerPricing: boolean;
};

const PriceTag = ({
  price,
  prevPrice,
  isPartnerPricing,
}: MainBannerProductImageProps): JSX.Element => {
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
          testID="price-tag-amount"
          type="body"
          size="large"
          weight="semibold"
        />
        {prevPrice ? (
          <Amount
            value={prevPrice}
            isAffixSubtle={false}
            suffix="none"
            testID="price-tag-prev-amount"
            marginRight="spacing.2"
            isStrikethrough={true}
            color="surface.text.gray.muted"
            type="body"
            size="large"
            weight="semibold"
          />
        ) : null}
      </Box>
      <StyledPriceTagImage
        src={isPartnerPricing ? PartnerPosPriceTagImage : PricingTagImage}
        alt="product price tag"
      />
    </Box>
  );
};

const MainBannerProductImage = ({
  price,
  prevPrice,
  isPartnerPricing,
}: MainBannerProductImageProps): JSX.Element => {
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
        <PriceTag price={price} prevPrice={prevPrice} isPartnerPricing={isPartnerPricing} />
        <StyledMainBannerImage src={MainBannerAndroidSmartPosImg} alt="android pos image" />
      </Box>
    </Box>
  );
};

export default MainBannerProductImage;
