import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProductPriceCard = styled.div(
  ({
    theme,
    isSelected,
    shouldShowPartnerPricing,
  }: {
    theme: Theme;
    isSelected: boolean;
    shouldShowPartnerPricing: boolean;
  }) => {
    let borderColor = theme.colors.surface.border.normal.lowContrast;
    if (isSelected) {
      borderColor = shouldShowPartnerPricing
        ? theme.colors.feedback.border.notice.highContrast
        : theme.colors.brand.primary[500];
    }
    const backgroundColor = shouldShowPartnerPricing
      ? theme.colors.feedback.background.notice.lowContrast
      : 'transparent';
    return `
        flex:1;
        padding: ${theme.spacing[3]}px; 
        border-radius: ${theme.border.radius.large}px;
        border: ${theme.border.width.thick}px solid ${borderColor};
        margin: ${theme.spacing[4]}px 0;
        cursor: pointer;
        background-color: ${backgroundColor};
    `;
  },
);

export const PartnerExclusivePriceImage = styled.img(
  ({ theme }: { theme: Theme }) => `
        margin-left: -18px;
        height: ${theme.spacing[9]}px;
        width: 170px;
        z-index: 1;
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
