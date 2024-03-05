import { Theme } from '@razorpay/blade/components';
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

export const PricingRowOfferTag = styled.div(
  ({ theme }: { theme: Theme }) => `
  padding: ${theme.spacing[3]}px;
  margin-bottom: ${theme.spacing[3]}px;
  border-radius: ${theme.border.radius.medium}px;
  width: fit-content;
  display: flex;
  background: linear-gradient(90deg, rgba(21, 102, 241, 0.18) 0.31%, rgba(21, 102, 241, 0.00) 108.4%);
  > p {
    font-style: italic;
  }
  `,
);
