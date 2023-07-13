import React from 'react';
import CRComparison from './CRComparison';
import { CardContainer } from './styled';

const TopSection = (): React.ReactElement => {
  return (
    <CardContainer>
      <CRComparison />
    </CardContainer>
  );
};

export default TopSection;
