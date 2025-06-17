import React, { useEffect } from 'react';
import { Box } from '@razorpay/blade/components';
import analytics, { SignUpEvents } from '@razorpay/universe-utils/analytics';

import { PAGE_READ_SUCCESS_MS } from 'apps/pos/src/app/views/SelfServe/constants';
import { useExecuteAfterDelay } from 'apps/pos/src/app/views/SelfServe/hooks';
import { MainContainer } from 'apps/pos/src/app/views/SelfServe/styles';
import { useScrollObserver } from 'apps/pos/src/app/views/SelfServe/utils/ScrollObserver';

import CatalogInfoBanner from './CatalogInfoBanner';
import MainBanner from './MainBanner/MainBanner';
import ProductCards from './ProductCards';
import ProductFeatureTable from './ProductFeatureTable';

const DeviceCatalog = (): JSX.Element => {
  const foldRef = React.useRef<HTMLDivElement>(null);

  const handlePageReadSuccess = () => {
    // Trigger pageReadSuccess event for page viewed after 15 seconds
    analytics.track_EXPERIMENTAL(SignUpEvents.pageReadSuccess, {
      pageType: 'POS Catalog',
    });
  };

  useExecuteAfterDelay({ callback: handlePageReadSuccess, delay: PAGE_READ_SUCCESS_MS });

  useEffect(() => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageViewed, {
      pageType: 'POS Catalog',
      orderId: '',
    });
  }, []);

  useScrollObserver(foldRef, () => {
    analytics.track_EXPERIMENTAL(SignUpEvents.pageSectionViewed, {
      sectionNumber: 4,
      section: 'Product Feature Table',
      l1FunnelStage: 'Device Exploration',
      l2FunnelStage: 'POS Catalog',
    });
  });

  return (
    <React.Fragment>
      <MainContainer>
        <Box testID="catalog-container">
          <MainBanner />
          <ProductCards />
          <CatalogInfoBanner />
        </Box>
      </MainContainer>
      <Box ref={foldRef}>
        <ProductFeatureTable
          instrumentation={{
            section: 'Device Comparison',
            subSection: 'Device Comparison',
            l1FunnelStage: 'Device Exploration',
            l2FunnelStage: 'POS Catalog',
          }}
          isElevated
        />
      </Box>
    </React.Fragment>
  );
};

export default DeviceCatalog;
