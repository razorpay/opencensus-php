import { COLORS } from 'merchant/containers/Home/RTUX/colors';
import styled from 'styled-components';

export const CarouselDataWidgetWrapper = styled.div<{ isMobile: boolean; hasAction: boolean }>(
  ({ theme, isMobile, hasAction }) => ({
    display: 'flex',
    backgroundColor: COLORS.backgroundPrimarySubtle,
    borderRadius: theme.border.radius.medium,
    padding: theme.spacing[5],
    height: isMobile ? 160 : 120,
    gap: theme.spacing[4],
    cursor: hasAction ? 'pointer' : 'unset',
  }),
);
