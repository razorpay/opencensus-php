import styled from 'styled-components';

export const DetailedPricingHeader = styled.div`
  cursor: pointer;
`;

export const DetailedPricingContent = styled.div(
  ({ isExpanded }: { isExpanded: boolean }) => `
    max-height: ${isExpanded ? '600px' : '0px'};
    overflow: hidden;
  `,
);
