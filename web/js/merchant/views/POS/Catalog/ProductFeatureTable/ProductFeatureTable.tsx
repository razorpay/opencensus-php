import React, { useState } from 'react';
import { Box, ChevronDownIcon, ChevronUpIcon, Link, Title } from '@razorpay/blade/components';
import analytics, {
  L1FunnelStageT,
  L2FunnelStageT,
  SignUpEvents,
} from '@razorpay/universe-utils/analytics';

import { PRODUCT_TABLE_LIST } from 'merchant/views/POS/constants';
import { useBladeBreakpoints } from 'merchant/views/POS/hooks';
import { ProductFeaturesColumn, ProductTableProduct } from 'merchant/views/POS/types';

import ProductFeatureTableItem from './ProductFeatureTableItem';
import { useScrollObserver } from 'merchant/views/POS/utils/ScrollObserver';

type ProductFeatureTableProps = {
  isElevated?: boolean;
  instrumentation?: {
    section: string;
    subSection: string;
    l1FunnelStage: L1FunnelStageT;
    l2FunnelStage: L2FunnelStageT;
  };
};

const ProductFeatureTable = ({
  isElevated,
  instrumentation,
}: ProductFeatureTableProps): JSX.Element => {
  const [isExpanded, setIsExapanded] = useState(false);
  const columns: ProductFeaturesColumn[][] = PRODUCT_TABLE_LIST.features;
  const products: ProductTableProduct[] = PRODUCT_TABLE_LIST.products;
  const { isMobile } = useBladeBreakpoints();
  const foldRef = React.useRef<HTMLDivElement>(null);

  const handleProductFeatureTableToggle = () => {
    if (instrumentation) {
      analytics.track_EXPERIMENTAL(SignUpEvents.linkClicked, {
        label: isExpanded ? 'Show Less' : 'Show More',
        whatsAppUpdates: 'No',
        ...instrumentation,
      });
    }
    setIsExapanded((expandedState) => !expandedState);
  };

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 5,
      section: 'Product Feature Table',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Product Description',
    });
  });

  return (
    <Box display="flex" flexDirection="column" overflow="hidden" ref={foldRef}>
      {isMobile ? (
        <Box marginBottom="spacing.5">
          <Title size="medium" textAlign="center">
            Choose the best devices for your business
          </Title>
        </Box>
      ) : null}
      <Box
        display="flex"
        margin="auto"
        overflowX="auto"
        width="100%"
        gap="spacing.4"
        paddingBottom="spacing.4"
      >
        {!isMobile ? (
          <ProductFeatureTableItem
            schema={columns}
            isColumn
            isExpanded={isExpanded}
            collapsibleIndex={1}
            isElevated={!!isElevated}
          />
        ) : null}
        {products.map((product) => (
          <ProductFeatureTableItem
            key={product.name}
            schema={columns}
            isColumn={false}
            product={product}
            isExpanded={isExpanded}
            collapsibleIndex={1}
            isElevated={!!isElevated}
          />
        ))}
      </Box>
      <Link
        variant="button"
        onClick={handleProductFeatureTableToggle}
        icon={isExpanded ? ChevronUpIcon : ChevronDownIcon}
        iconPosition="right"
        marginY="spacing.4"
        marginX="auto"
      >
        Show {isExpanded ? 'Less' : 'More'}
      </Link>
    </Box>
  );
};

export default ProductFeatureTable;
