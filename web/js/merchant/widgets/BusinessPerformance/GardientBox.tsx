import styled from 'styled-components';

import { createGradientMap } from './helpers';

export const GradientBox = styled.div(({ theme, variant, isMobile }) => {
  const direction = isMobile ? '90deg' : '180deg';
  const validVariant = ['positive', 'negative'].includes(variant) ? variant : 'positive';

  return `
      background: ${createGradientMap({ direction, theme })[validVariant]};
      border-color: ${theme.colors.interactive.border[validVariant].highlighted};
      padding: ${theme.spacing[6]}px;

      ${
        isMobile
          ? `
        border-top-left-radius: ${theme.border.radius.large}px;
        border-bottom-left-radius: ${theme.border.radius.large}px;
        border-left-width: ${theme.border.width.thicker}px;
        border-left-style: solid;
      `
          : `
        border-top-left-radius: ${theme.border.radius.large}px;
        border-top-right-radius: ${theme.border.radius.large}px;
        border-top-width: ${theme.border.width.thicker}px;
        border-top-style: solid;
      `
      }
    `;
});
