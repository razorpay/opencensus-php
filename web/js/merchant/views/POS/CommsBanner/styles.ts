import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';
import { CommsBannerItemVariants } from 'merchant/views/POS/types';

const bgColorFn = ({ theme }: { theme: Theme }) => ({
  positive: {
    bgColor: theme.colors.feedback.background.positive.highContrast,
    bgShadow: theme.colors.feedback.background.positive.lowContrast,
  },
  negative: {
    bgColor: theme.colors.feedback.background.negative.highContrast,
    bgShadow: theme.colors.feedback.background.negative.lowContrast,
  },
  notice: {
    bgColor: theme.colors.feedback.background.notice.highContrast,
    bgShadow: theme.colors.feedback.background.notice.lowContrast,
  },
  information: {
    bgColor: theme.colors.feedback.background.information.highContrast,
    bgShadow: theme.colors.feedback.background.information.lowContrast,
  },
  neutral: {
    bgColor: theme.colors.brand.gray[400].lowContrast,
    bgShadow: theme.colors.brand.gray[300].lowContrast,
  },
});

export const IconContainer = styled.div(
  ({ theme, variant }: { theme: Theme; variant: CommsBannerItemVariants }) => `
    padding: ${theme.spacing[2]}px;
    background-color: ${bgColorFn({ theme })?.[variant]?.bgColor};
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: ${theme.border.radius.round};
    width: 28px;
    height: 28px;
    `,
);

export const IconShadow = styled.div(
  ({ theme, variant }: { theme: Theme; variant: CommsBannerItemVariants }) => `
    background-color: ${bgColorFn({ theme })?.[variant]?.bgShadow};
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: ${theme.border.radius.round};
    width: 40px;
    height: 40px;
    `,
);

export const CommsBannerItemConnctor = styled.div(
  ({
    theme,
    isHidden,
    isMobileOrTablet,
    variant,
  }: {
    theme: Theme;
    isHidden: boolean;
    isMobileOrTablet?: boolean;
    variant: CommsBannerItemVariants;
  }) => `
  border-${isMobileOrTablet ? 'right' : 'top'}: ${theme.border.width.thicker}px dashed ${
    bgColorFn({ theme })?.[variant]?.bgColor
  };
  opacity: ${isHidden ? 0 : 1};
  margin: 0 ${theme.spacing[1]}px 0 0;
  height: 2px;
  flex: 1;
  `,
);

export const StyledImage = styled.img`
  height: 100%;
  width: 100%;
  transform: scale(1.18);
`;
