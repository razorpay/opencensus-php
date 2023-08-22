import React from 'react';
import CRComparison from './CRComparison';
import IndustryCRComparison from './IndustryCrComparison';
import { CardContainer } from './styled';

const TopSection = ({ category = '' }): React.ReactElement => {
  return (
    <CardContainer>
      <CRComparison />
      {category && <IndustryCRComparison category={category} />}
    </CardContainer>
  );
};

export default TopSection;
