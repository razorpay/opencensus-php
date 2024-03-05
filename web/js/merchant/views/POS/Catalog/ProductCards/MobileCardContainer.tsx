import React, { useContext } from 'react';
import { Box, Title, Heading, Text, Amount } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import AddToCartButton from 'merchant/views/POS/Cart/AddToCartButton';
import OfferStrip from 'merchant/views/POS/Catalog/OfferStrip';
import AmountWithStrikeThrough from 'merchant/views/POS/ProductDescription/ProductPriceCards/AmountWithStrikeThrough';
import { PRODUCT_PLANS } from 'merchant/views/POS/constants';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getPricingByProduct, getProductFromProductDescriptions } from 'merchant/views/POS/helpers';

import { MobileEllpise, StyledProductCardImage } from './styles';

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

  const { monthly, setupFee, offer } = getPricingByProduct({ productDescription });

  const isValidOffer =
    productDescription?.offer &&
    offer &&
    offer?.prevMonthly !== null &&
    offer?.prevSetupFee !== null &&
    offer?.nextMonthly !== null;

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
        {productDescription?.offer ? (
          <Box marginBottom="spacing.5">
            <OfferStrip text={productDescription.offer.offerText} type="light" />
          </Box>
        ) : null}
        <Title size="medium" textAlign="center">
          {productDescription.productTitle}
        </Title>
        <Heading size="medium" textAlign="center" marginBottom="spacing.8" type="subdued">
          {cardDescription}
        </Heading>
        <Box marginBottom="spacing.7">
          {isValidOffer ? (
            <React.Fragment>
              <Heading
                textAlign="center"
                weight="regular"
                type="subtle"
                testID="monthly-offer-amount-text"
              >
                <Amount
                  value={offer?.nextMonthly}
                  isAffixSubtle={false}
                  suffix="none"
                  size="heading-large-bold"
                />{' '}
                <AmountWithStrikeThrough value={offer?.prevMonthly} size="heading-small-bold" />{' '}
                /month after 3 months*
              </Heading>
              <Text
                textAlign="center"
                marginBottom="spacing.3"
                type="subtle"
                testID="setup-offer-amount-text"
              >
                {' '}
                <Amount
                  value={setupFee}
                  suffix="none"
                  isAffixSubtle={false}
                  size="heading-small-bold"
                />{' '}
                <AmountWithStrikeThrough value={offer?.prevSetupFee} size="heading-small-bold" />{' '}
                setup fee
              </Text>
            </React.Fragment>
          ) : (
            <React.Fragment>
              <Heading textAlign="center">
                <Amount
                  value={monthly}
                  isAffixSubtle={false}
                  suffix="none"
                  size="heading-large-bold"
                />{' '}
                monthly subscription
              </Heading>
              <Text textAlign="center" marginBottom="spacing.3">
                +{' '}
                <Amount
                  value={setupFee}
                  suffix="none"
                  isAffixSubtle={false}
                  size="body-medium-bold"
                />{' '}
                one time setup fee
              </Text>
            </React.Fragment>
          )}
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
