import React, { useContext } from 'react';
import { Amount, Box, Divider, Text, BoxProps } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import PosCatalogPartnerExclusivePrice from 'assets/partner-dashboard/PosCatalogPartnerExclusivePrice.svg';
import { useSplitzService } from 'common/splitz';
import OfferStrip from 'merchant/views/POS/Catalog/OfferStrip';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { getProductFromProductDescriptions, fetchProductOffers } from 'merchant/views/POS/helpers';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { PricingTypes } from 'merchant/views/POS/types';

import OfferPriceCardContent from './OfferPriceCardContent';
import { ProductPriceCard, PartnerExclusivePriceImage } from './styles';

type ProductPriceCards = {
  selectedPricing: PricingTypes;
  productCode: string;
  onPricingPlanChange: (type: PricingTypes) => void;
};

const ProductPriceCards = ({
  selectedPricing,
  productCode,
  onPricingPlanChange,
}: ProductPriceCards): JSX.Element | null => {
  const { matchedBreakpoint } = useBladeBreakpoints();
  const { state } = useContext(PosDeviceStoreContext);
  const { abExperiments } = useSplitzService();
  const { isEnabled: isOfferEnabled } = fetchProductOffers({ abExperiments });
  const { productDescriptions } = state;
  const product = getProductFromProductDescriptions({ code: productCode, productDescriptions });

  const handleOnPricingCardSelect = (type) => {
    analytics.track_EXPERIMENTAL(SignUpEvents.websiteCtaClicked, {
      label: type,
      section: 'Device',
      whatsAppUpdates: 'No',
      subSection: 'Android Smart POS/Android Smart Mini POS/ Mobile POS (m-POS)',
      l1FunnelStage: 'Device Consideration',
      l2FunnelStage: 'POS Product Description',
    });

    onPricingPlanChange?.(type as PricingTypes);
  };

  if (!product || !product.pricing) return null;

  const { pricing, offer } = product;
  const isPartnerPricing = product?.isPartnerPricing;
  return (
    <Box>
      {product?.offer?.pdpOfferText ? (
        <OfferStrip
          text={isPartnerPricing ? product.offer.partnerPdpOfferText : product.offer.pdpOfferText}
          type="light"
          isPartnerPricing={isPartnerPricing}
        />
      ) : null}
      <Box
        display={matchedBreakpoint === 'xl' ? 'flex' : 'block'}
        width="100%"
        gap="spacing.4"
        marginBottom="spacing.4"
      >
        {pricing.map((pricing) => {
          const { type, breakups, subText } = pricing;
          const isShowOfferPriceCard = !!offer;
          const isSelected = selectedPricing === type;
          const shouldShowPartnerPricing = isPartnerPricing && isSelected && !isOfferEnabled;
          let priceContainerBackground: BoxProps['backgroundColor'] =
            'surface.background.level2.lowContrast';

          if (shouldShowPartnerPricing) {
            priceContainerBackground = 'transparent';
          } else if (isSelected) {
            priceContainerBackground = 'surface.background.level3.lowContrast';
          }
          return (
            <ProductPriceCard
              key={type}
              isSelected={isSelected}
              onClick={() => handleOnPricingCardSelect(type)}
              aria-selected={isSelected}
              data-testid={`${type}-price-card`}
              shouldShowPartnerPricing={shouldShowPartnerPricing}
            >
              {shouldShowPartnerPricing ? (
                <PartnerExclusivePriceImage
                  src={PosCatalogPartnerExclusivePrice}
                  alt="Pos Catalog Partner Exclusive"
                />
              ) : null}
              <Box
                backgroundColor={priceContainerBackground}
                paddingY={shouldShowPartnerPricing ? 'spacing.2' : 'spacing.5'}
                paddingX="spacing.3"
                height={shouldShowPartnerPricing ? 'auto' : '100%'}
                position="relative"
                display="flex"
                flexDirection="column"
                justifyContent="center"
                borderRadius="large"
              >
                {isShowOfferPriceCard ? (
                  <OfferPriceCardContent pricing={pricing} />
                ) : (
                  <React.Fragment>
                    {breakups.map(({ key, value, description }, index) => (
                      <Box
                        key={key}
                        display="flex"
                        alignItems="center"
                        justifyContent="space-between"
                        marginBottom="spacing.4"
                      >
                        <Text size="large">{description}</Text>
                        <Text size="large">
                          {index > 0 ? '+ ' : null}
                          <Amount
                            value={value}
                            suffix="none"
                            size="heading-small-bold"
                            isAffixSubtle={false}
                          />
                        </Text>
                      </Box>
                    ))}
                    <Box>
                      {breakups.length > 1 ? <Divider /> : null}
                      <Text marginTop="spacing.4">{subText}</Text>
                    </Box>
                  </React.Fragment>
                )}
              </Box>
            </ProductPriceCard>
          );
        })}
      </Box>
    </Box>
  );
};

export default ProductPriceCards;
