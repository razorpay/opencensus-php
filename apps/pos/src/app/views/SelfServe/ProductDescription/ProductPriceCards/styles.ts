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
    let borderColor = theme.colors.surface.border.gray.muted;
    if (isSelected) {
      borderColor = shouldShowPartnerPricing
        ? theme.colors.feedback.border.notice.intense
        : theme.colors.surface.background.primary.intense;
    }
    const backgroundColor = shouldShowPartnerPricing
      ? theme.colors.feedback.background.notice.subtle
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
