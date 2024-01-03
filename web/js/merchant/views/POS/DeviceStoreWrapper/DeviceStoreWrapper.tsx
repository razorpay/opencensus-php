import React, { useEffect, useRef } from 'react';
import { Box } from '@razorpay/blade/components';
import { useLocation, useSearchParams } from 'react-router-dom';

import ProductWrapper from 'common/ui/ProductWrapper';
import { ProductWrapperStyled } from 'merchant/views/POS/styles';

type DeviceStoreWrapperProps = {
  tabs: {
    title: string;
    url: string;
    isMatchStartsWith: boolean;
    onTabClick?: () => void;
  }[];
  extra: JSX.Element;
  children: React.ReactChild;
  isTabsRequired: boolean;
  customHeaderRightClass?: string;
};

const DeviceStoreWrapper = ({
  tabs,
  extra,
  children,
  isTabsRequired,
  customHeaderRightClass,
}: DeviceStoreWrapperProps): JSX.Element => {
  const productWrapperRef = useRef<HTMLDivElement | null>(null);
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();

  useEffect(() => {
    if (productWrapperRef?.current) {
      const isFocusProduct = searchParams.get('focusProduct') === 'true';
      const productWrapperTopOffset = productWrapperRef?.current?.offsetTop;
      if (isFocusProduct && productWrapperTopOffset > 1) {
        window.scrollTo({
          top: productWrapperTopOffset,
          behavior: 'smooth',
        });
        setSearchParams({ focusProduct: 'focused' });
      }
    }
  }, [location.search, productWrapperRef, searchParams, setSearchParams]);

  return isTabsRequired ? (
    <ProductWrapperStyled ref={productWrapperRef}>
      <ProductWrapper tabsData={tabs} extra={extra} customHeaderRightClass={customHeaderRightClass}>
        {children}
      </ProductWrapper>
    </ProductWrapperStyled>
  ) : (
    <Box>{children}</Box>
  );
};
export default DeviceStoreWrapper;
