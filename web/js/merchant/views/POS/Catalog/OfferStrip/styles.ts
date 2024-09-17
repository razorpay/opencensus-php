import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const OfferStripContainer = styled.div(
  ({
    theme,
    type,
    isPartnerPricing,
    isRounded,
    size,
  }: {
    theme: Theme;
    type: 'light' | 'dark';
    isPartnerPricing: boolean;
    isRounded: boolean;
    size: 'small' | 'large';
  }) => {
    let backgroundColor =
      'linear-gradient(90deg, rgba(21, 102, 241, 0.18) 0.31%, rgba(21, 102, 241, 0.00) 108.4%)';
    if (type === 'dark') {
      backgroundColor = isPartnerPricing
        ? 'hsla(32, 89%, 37%, 1)'
        : theme.colors.surface.background.primary.intense;
    } else if (isPartnerPricing) {
      backgroundColor = 'linear-gradient(90deg, hsla(36, 57%, 86%, 1), hsla(219, 59%, 92%, 0.1))';
    }
    return `
    padding: ${
      size === 'small' ? `${theme.spacing[2]}px ${theme.spacing[3]}px` : `${theme.spacing[3]}px`
    };
    border-radius: ${isRounded ? theme.border.radius['2xlarge'] : theme.border.radius.medium}px;
    width: fit-content;
    display: flex;
    background: ${backgroundColor};
    `;
  },
);
