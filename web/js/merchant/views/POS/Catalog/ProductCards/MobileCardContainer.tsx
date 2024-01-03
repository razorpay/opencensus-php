import React, { useContext } from 'react';
import { Box, Title, Heading, Text, Amount } from '@razorpay/blade/components';

import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';

import { MobileEllpise, StyledProductCardImage } from './styles';
import { useNavigate } from 'react-router-dom';
import { PRODUCT_PLANS } from 'merchant/views/POS/constants';

type MobileCardContainer = {
  code: string;
  image: string;
  cardDescription: string;
};

const MobileCardContainer = ({
  code,
  image,
  cardDescription,
}: MobileCardContainer): JSX.Element | null => {
  const { state } = useContext(PosDeviceStoreContext);
  const navigate = useNavigate();
  const { productDescriptions } = state;
  const productDescription = getProductFromProductDescriptions({ code, productDescriptions });

  if (!productDescription || !productDescription.pricing) return null;

  const { monthly, setupFee } = getPricingByProduct({ productDescription });

  const handleOnCardClick = () => {
    navigate(`/pos/catalog/${productDescription.code}`);
  };

  return (
    <div onClick={handleOnCardClick}>
      <Box
        backgroundColor="surface.background.level1.lowContrast"
        paddingTop="spacing.8"
        borderRadius="large"
        position="relative"
        overflow="hidden"
        marginBottom="spacing.5"
      >
        <Title size="medium" textAlign="center">
          {productDescription.productTitle}
        </Title>
        <Heading size="medium" textAlign="center" marginBottom="spacing.8" type="subdued">
          {cardDescription}
        </Heading>
        <Box marginBottom="spacing.7">
          <Heading textAlign="center">
            <Amount value={monthly} isAffixSubtle={false} suffix="none" size="heading-large-bold" />{' '}
            monthly subscription
          </Heading>
          <Text textAlign="center" marginBottom="spacing.1">
            +{' '}
            <Amount value={setupFee} suffix="none" isAffixSubtle={false} size="body-medium-bold" />{' '}
            one time setup fee
          </Text>
          <Text textAlign="center" type="muted" size="small">
            *Lifetime Pricing also available.
          </Text>
        </Box>
        <Box
          display="flex"
          justifyContent="center"
          zIndex={1}
          position="relative"
          marginBottom="spacing.10"
        >
          <AddToCartButton
            productCode={productDescription.code}
            plan={PRODUCT_PLANS.MONTHLY}
            openCartOnUpdate
          />
        </Box>
        <Box
          width="100%"
          display="flex"
          alignItems="center"
          flexDirection="column"
          zIndex={1}
          position="relative"
        >
          <Box>
            <StyledProductCardImage src={image} alt="mini pos mobile image" />
          </Box>
        </Box>
        <Box
          position="absolute"
          maxWidth="800px"
          width="100%"
          height="100%"
          top="0px"
          left="0px"
          backgroundPosition="65% center"
          backgroundSize="cover"
          backgroundRepeat="no-repeat"
          backgroundImage={`url("${MainBannerBackdropImage}")`}
        />
        <MobileEllpise />
      </Box>
    </div>
  );
};

export default MobileCardContainer;
