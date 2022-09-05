import React, { ReactNode } from 'react';
import ErrorBoundary, { Teams, Ranks, FallbackComponentProps } from 'common/new-ui/ErrorBoundary';

type GrowthAssetEBPropsT = {
  children: ReactNode;
  shouldShowDefaultFb?: boolean;
  FallbackComponent?: FallbackComponentProps;
};

const EmptyFallback = () => null;

const GrowthAssetEB = ({
  shouldShowDefaultFb,
  children,
  FallbackComponent,
}: GrowthAssetEBPropsT): JSX.Element => {
  let FallbackComponentProp;

  if (FallbackComponent) FallbackComponentProp = FallbackComponent;
  else if (!shouldShowDefaultFb) FallbackComponentProp = EmptyFallback;

  return (
    <ErrorBoundary
      resetOnProps
      team={Teams.PLATFORM_GROWTH}
      rank={Ranks.P1}
      FallbackComponent={FallbackComponentProp}
    >
      {children}
    </ErrorBoundary>
  );
};

export default GrowthAssetEB;
