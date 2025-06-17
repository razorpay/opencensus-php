import React, { useContext } from 'react';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import PosProductCard, {
  Variant,
} from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/PosProductCard';
import PosProductCardMobile from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/PosProductCardMobile';
import { ProductCardsContainer } from 'apps/pos/src/app/views/SelfServe/Catalog/ProductCards/styles';
import { SOUNDBOX, STANDEEANDSTICKER } from 'apps/pos/src/app/views/SelfServe/constants';
import { getAllPosProducts } from 'apps/pos/src/app/views/SelfServe/constants/ProductCards';
import { PosDeviceStoreContext } from 'apps/pos/src/app/views/SelfServe/context';
import { useBladeBreakpoints } from 'apps/pos/src/app/views/SelfServe/hooks';
import { ProductDescription, ProductPlans } from 'apps/pos/src/app/views/SelfServe/types';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';

const getPosCatalog = (
  isMobile: boolean,
  isSoundboxEnabled: boolean | undefined,
  productDescriptions: ProductDescription[],
  soundboxProducts: string[],
) => {
  if (!isSoundboxEnabled) {
    return getAllPosProducts(productDescriptions, isMobile).filter(
      (product) => !soundboxProducts.includes(product.productDescription?.code as string),
    );
  }
  return getAllPosProducts(productDescriptions, isMobile);
};

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

  const Products = getPosCatalog(
    isMobile,
    isSoundboxEnabled,
    productDescriptions,
    soundboxProducts,
  );

  return (
    <ProductCardsContainer>
      {isMobile ? (
        <React.Fragment>
          {Products.map(
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
                key={`${productDescription?.code}-mobile`}
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
          {Products.map(
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
                key={`${productDescription?.code}-desktop`}
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
