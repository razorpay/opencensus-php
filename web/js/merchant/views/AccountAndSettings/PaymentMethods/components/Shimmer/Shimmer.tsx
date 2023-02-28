import React from 'react';
import {
  PaymentMethodsStyledTabContentContainer,
  SectionHeader,
} from 'merchant/views/AccountAndSettings/PaymentMethods/components/Section/Styled';
import Shimmer from 'common/components/Shimmer';
import {
  SectionContentShimmer,
  InnerSectionBox,
} from 'merchant/views/AccountAndSettings/PaymentMethods/components/Shimmer/Styled';

const SectionShimmer = () => {
  return (
    <PaymentMethodsStyledTabContentContainer data-testid="payment-method-tabs-shimmer">
      <SectionHeader>
        <div>
          <Shimmer height="28px" width="100px" variant="rounded" styles={{ marginBottom: '8px' }} />
          <Shimmer height="20px" width="230px" variant="rounded" />
        </div>
        <Shimmer height="20px" width="250px" variant="rounded" />
      </SectionHeader>
      <SectionContentShimmer>
        <Shimmer height="20px" width="150px" variant="rounded" styles={{ marginBottom: '12px' }} />
        <InnerSectionBox>
          <Shimmer height="56px" width="400px" variant="rounded" />
          <Shimmer height="56px" width="400px" variant="rounded" />
          <Shimmer height="56px" width="400px" variant="rounded" />
          <Shimmer height="56px" width="400px" variant="rounded" />
        </InnerSectionBox>
      </SectionContentShimmer>
    </PaymentMethodsStyledTabContentContainer>
  );
};

export default SectionShimmer;
