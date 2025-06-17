import React, { useContext } from 'react';
import { Box, Heading, Text, Amount } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';

import MainBannerBackdropImage from 'assets/pos/main-banner/mainbannerbackground.webp';
import { useSplitzService } from 'common/splitz';
import AddToCartButton from 'apps/pos/src/app/views/SelfServe/Cart/AddToCartButton';
import OfferStrip from 'apps/pos/src/app/views/SelfServe/Catalog/OfferStrip';
import { PartnerExclusivePriceContainer } from 'apps/pos/src/app/views/SelfServe/PartnerExclusiveContainer';
import { PRODUCT_PLANS } from 'apps/pos/src/app/views/SelfServe/constants';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import {
  getPricingByProduct,
  getProductFromProductDescriptions,
  fetchProductOffers,
} from 'apps/pos/src/app/views/SelfServe/helpers';

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
  const { abExperiments } = useSplitzService();
  const { isEnabled: isOfferEnabled } = fetchProductOffers({ abExperiments });
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

  const isPartnerPricing = productDescription?.isPartnerPricing;
  return (
    <div onClick={handleOnCardClick}>
      <Box
        backgroundColor="surface.background.gray.subtle"
        paddingTop="spacing.8"
        borderRadius="large"
        position="relative"
        overflow="hidden"
        marginBottom="spacing.5"
      >
        {productDescription?.offer ? (
          <Box marginBottom="spacing.5">
            <OfferStrip
              text={
                isPartnerPricing
                  ? productDescription.offer.partnerOfferText
                  : productDescription.offer.offerText
              }
              type="light"
              isPartnerPricing={isPartnerPricing}
            />
          </Box>
        ) : null}
        <Heading textAlign="center" size="xlarge">
          {productDescription.productTitle}
        </Heading>
        <Heading
          textAlign="center"
          marginBottom="spacing.8"
          size="small"
          color="surface.text.gray.muted"
        >
          {cardDescription}
        </Heading>
        <PartnerExclusivePriceContainer
          isPartnerPricing={isPartnerPricing && !isOfferEnabled}
          type="PRODUCT_CARD"
          isMobileCard
        >
          <Box marginBottom="spacing.7">
            {isValidOffer ? (
              <React.Fragment>
                <Text
                  textAlign="center"
                  weight="regular"
                  testID="monthly-offer-amount-text"
                  size="large"
                  color="surface.text.gray.subtle"
                >
                  <Amount
                    value={offer?.nextMonthly}
                    isAffixSubtle={false}
                    suffix="none"
                    size="medium"
                    weight="semibold"
                    type="heading"
                  />{' '}
                  <Amount
                    value={offer?.prevMonthly}
                    isAffixSubtle={false}
                    suffix="none"
                    marginRight="spacing.2"
                    isStrikethrough={true}
                    color="surface.text.gray.muted"
                    size="large"
                    weight="semibold"
                  />{' '}
                  /month after 3 months*
                </Text>
                <Text
                  textAlign="center"
                  marginBottom="spacing.3"
                  testID="setup-offer-amount-text"
                  color="surface.text.gray.subtle"
                >
                  {' '}
                  <Amount
                    value={setupFee}
                    suffix="none"
                    isAffixSubtle={false}
                    size="large"
                    weight="semibold"
                  />{' '}
                  <Amount
                    value={offer?.prevSetupFee}
                    isAffixSubtle={false}
                    suffix="none"
                    marginRight="spacing.2"
                    isStrikethrough={true}
                    color="surface.text.gray.muted"
                    size="large"
                    weight="semibold"
                  />{' '}
                  setup fee
                </Text>
              </React.Fragment>
            ) : (
              <React.Fragment>
                <Text textAlign="center" size="large">
                  <Amount
                    value={monthly}
                    isAffixSubtle={false}
                    suffix="none"
                    size="medium"
                    weight="semibold"
                    type="heading"
                  />{' '}
                  monthly subscription
                </Text>
                <Text textAlign="center" marginBottom="spacing.3">
                  +{' '}
                  <Amount
                    value={setupFee}
                    suffix="none"
                    isAffixSubtle={false}
                    size="medium"
                    weight="semibold"
                  />{' '}
                  one time setup fee
                </Text>
              </React.Fragment>
            )}
            <Text textAlign="center" size="small" color="surface.text.gray.muted">
              *Lifetime Pricing also available.
            </Text>
          </Box>
        </PartnerExclusivePriceContainer>
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
