import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';
import { CommsBannerItemVariants } from 'merchant/views/POS/types';

const bgColorFn = ({ theme }: { theme: Theme }) => ({
  positive: {
    bgColor: theme.colors.feedback.background.positive.intense,
    bgShadow: theme.colors.feedback.background.positive.subtle,
  },
  negative: {
    bgColor: theme.colors.feedback.background.negative.intense,
    bgShadow: theme.colors.feedback.background.negative.subtle,
  },
  notice: {
    bgColor: theme.colors.feedback.background.notice.intense,
    bgShadow: theme.colors.feedback.background.notice.subtle,
  },
  information: {
    bgColor: theme.colors.feedback.background.information.intense,
    bgShadow: theme.colors.feedback.background.information.subtle,
  },
  neutral: {
    bgColor: theme.colors.interactive.border.gray.faded,
    bgShadow: theme.colors.surface.background.gray.subtle,
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
