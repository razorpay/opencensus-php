import React from 'react';
import { ShimmerBar, ShimmerGroup } from './styled';

const NavGroupShimmer = (): JSX.Element => {
  return (
    <ShimmerGroup data-testid="shimmer-group">
      <ShimmerBar />
      <ShimmerBar />
      <ShimmerBar />
      <ShimmerBar />
    </ShimmerGroup>
  );
};

export default NavGroupShimmer;
