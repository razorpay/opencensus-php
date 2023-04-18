import React from 'react';
import { EcosystemLoaderContainer, ShimmerGroup } from 'merchant/views/EcosystemDowntimes/styles';
import Shimmer from 'common/components/Shimmer';

const EcosystemHealthLoader = (): JSX.Element => {
  return (
    <EcosystemLoaderContainer data-testid="ecosystem-health-loader">
      {[1, 2, 3, 4].map((value) => (
        <ShimmerGroup key={value}>
          <Shimmer height="3vh" width="95%" variant="rounded" />
          <Shimmer height="2.5vh" width="85%" variant="rounded" />
          <Shimmer height="76vh" width="95%" variant="rounded" />
        </ShimmerGroup>
      ))}
    </EcosystemLoaderContainer>
  );
};

export default EcosystemHealthLoader;
