import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProductListFeatureIcon = styled.img(
  ({ theme }: { theme: Theme }) => `
    margin-top: ${theme.spacing[2]}px;
    height: 20px;
  `,
);

export const ProductImage = styled.img`
  max-height: 90%;
`;

export const ProductItemContainer = styled.div`
  width: 280px;
  &:first-child {
    margin-left: auto;
  }
  &:last-child {
    margin-right: auto;
  }
`;
