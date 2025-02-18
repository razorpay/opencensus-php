import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import { BAR_VARIANTS } from './constants';
import { BarLabelAlignment, BarProps, BarVariant, StyledBarProps } from './types';

const getBarBackground = (variant: BarVariant) => {
  switch (variant) {
    case BAR_VARIANTS.NEUTRAL:
      return `linear-gradient(#cbd5e2, #cbd5e200);
        `;
    case BAR_VARIANTS.POSITIVE:
      return `linear-gradient(#00874380, #E6EBF00D);
        `;
    case BAR_VARIANTS.NEGATIVE:
      return `linear-gradient(#FF8880, #E6EBF00D);`;
    default:
      return `linear-gradient(#cbd5e2, #cbd5e200);
        `;
  }
};

const getBarBorderColour = ({ theme, variant }: Omit<StyledBarProps, 'height'>) => {
  switch (variant) {
    case BAR_VARIANTS.NEUTRAL:
      return theme.colors.feedback.border.neutral.intense;
    case BAR_VARIANTS.POSITIVE:
      return theme.colors.feedback.border.positive.intense;
    case BAR_VARIANTS.NEGATIVE:
      return theme.colors.feedback.border.negative.intense;
    default:
      return theme.colors.feedback.border.neutral.intense;
  }
};

const getBarHeight = (height: 'low' | 'high') => {
  switch (height) {
    case 'low':
      return '60%';
    case 'high':
      return '70%';
    default:
      return '60%';
  }
};

export const BarOuter = styled.div(
  ({ theme, variant, height }: StyledBarProps) => `
    width: 50px;
    height: ${getBarHeight(height)};
    border-top-left-radius: ${theme.border.radius.medium}px;
    border-top-right-radius: ${theme.border.radius.medium}px;
    background: ${theme.colors.surface.background.gray.intense};
    border-top: ${theme.border.width.thicker}px solid ${getBarBorderColour({ theme, variant })};
    transition: height ${theme.motion.duration.quick}ms ${theme.motion.easing.standard};
    position: relative;
  `,
);

export const BarInner = styled.div(
  ({ variant }: Omit<BarProps, 'height'>) => `
    width: 100%;
    height: 100%;
    background: ${getBarBackground(variant)};
    position: absolute;
    z-index: 1;
  `,
);

export const ProductTypeBadge = styled.div(
  ({ theme }: { theme: Theme }) => `
    position: absolute;
    bottom: 0px;
    background: red;
    max-width: 100%;
    text-align: center;
    padding: ${theme.spacing[3]}px ${theme.spacing[11]}px;
    border-top-left-radius: ${theme.border.radius.max}px;
    border-top-right-radius: ${theme.border.radius.max}px;
    background: linear-gradient(#f1f5fa, #f1f5fa00);
    overflow: hidden;
  `,
);

export const BarLabelWrapper = styled.div(
  ({ theme, align }: { theme: Theme; align: BarLabelAlignment }) => `
    text-align: center;
    position: absolute;
    overflow: hidden;
    background: ${theme.colors.surface.background.gray.intense};
    top: ${theme.spacing[5]}px;
    right: ${align === 'left' ? `${theme.spacing[4]}px` : 'auto'};
    left: ${align === 'right' ? `${theme.spacing[4]}px` : 'auto'};
    z-index: 2;
    border-radius: ${theme.border.radius.max}px;
    width: max-content;
  `,
);
