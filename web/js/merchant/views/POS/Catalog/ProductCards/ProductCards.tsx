import React, { useContext } from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import PosProductCard, { Variant } from 'merchant/views/POS/Catalog/ProductCards/PosProductCard';
import PosProductCardMobile from 'merchant/views/POS/Catalog/ProductCards/PosProductCardMobile';
import { ProductCardsContainer } from 'merchant/views/POS/Catalog/ProductCards/styles';
import { SOUNDBOX, STANDEEANDSTICKER } from 'merchant/views/POS/constants';
import { getAllPosProducts } from 'merchant/views/POS/constants/ProductCards';
import { PosDeviceStoreContext } from 'merchant/views/POS/context';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { ProductPlans } from 'merchant/views/POS/types';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

const ProductCards = (): JSX.Element => {
  const { isMobile } = useBladeBreakpoints();
  const foldRef = React.useRef<HTMLDivElement>(null);
  const { state } = useContext(PosDeviceStoreContext);
  const { productDescriptions, isSoundboxEnabled } = state;
  const soundboxProducts = [SOUNDBOX.code, STANDEEANDSTICKER.code];

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 2,
      section: 'Product Cards',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
    });
  });

  const getPosCatalog = () => {
    if (!isSoundboxEnabled) {
      return getAllPosProducts(productDescriptions, isMobile).filter(
        (product) => !soundboxProducts.includes(product.productDescription?.code as string),
      );
    }
    return getAllPosProducts(productDescriptions, isMobile);
  };

  return (
    <ProductCardsContainer>
      {isMobile ? (
        <React.Fragment>
          {getPosCatalog().map(
            ({
              productDescription,
              title,
              description,
              imageSrc,
              tncText,
              plan,
              pricingDescription,
              cta,
              footer,
            }) => (
              <PosProductCardMobile
                key={productDescription?.code}
                productDescription={productDescription}
                title={title}
                description={description}
                imageSrc={imageSrc}
                tncText={tncText}
                footer={footer}
                cta={cta}
                plan={plan as ProductPlans}
                pricingDescription={pricingDescription}
              />
            ),
          )}
        </React.Fragment>
      ) : (
        <React.Fragment>
          {getPosCatalog().map(
            ({
              productDescription,
              title,
              description,
              imageSrc,
              tncText,
              plan,
              variant,
              pricingDescription,
              cta,
              footer,
            }) => (
              <PosProductCard
                key={productDescription?.code}
                productDescription={productDescription}
                title={title}
                description={description}
                imageSrc={imageSrc}
                tncText={tncText}
                footer={footer}
                cta={cta}
                variant={variant as Variant}
                plan={plan as ProductPlans}
                pricingDescription={pricingDescription}
              />
            ),
          )}
        </React.Fragment>
      )}
    </ProductCardsContainer>
  );
};

export default ProductCards;
