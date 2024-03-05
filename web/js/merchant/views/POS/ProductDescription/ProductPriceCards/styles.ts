import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProductPriceCard = styled.div(
  ({ theme, isSelected }: { theme: Theme; isSelected: boolean }) => `
        flex:1;
        padding: ${theme.spacing[3]}px; 
        border-radius: ${theme.border.radius.large}px;
        border: ${theme.border.width.thick}px solid ${
    isSelected ? theme.colors.brand.primary[500] : theme.colors.surface.border.normal.lowContrast
  };
        margin: ${theme.spacing[4]}px 0;
        cursor: pointer;
    `,
);

export const OfferAmountComponentWrapper = styled.span(
  ({ theme }: { theme: Theme }) => `
  position: relative;
  span,
  div {
    color: ${theme.colors.surface.text.subdued.lowContrast};
  }
`,
);
