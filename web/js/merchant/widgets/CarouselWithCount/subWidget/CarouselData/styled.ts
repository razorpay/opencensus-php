import styled from 'styled-components';

const trimmedLines = '2';
const unTrimmedLines = '5';

export const CarouselDataWidgetTitleWrapper = styled.div<{ isMobile: boolean }>(
  ({ isMobile, theme }) => ({
    opacity: 1,
    maxHeight: 100,
    ...(isMobile
      ? {}
      : {
          transition: `opacity ${theme.motion.duration.xgentle}ms, max-height ${theme.motion.duration.xgentle}ms`,
        }),
  }),
);

export const CarouselDataWidgetActionWrapper = styled.div<{ isMobile: boolean }>(
  ({ isMobile, theme }) => ({
    opacity: isMobile ? 1 : 0,
    display: isMobile ? 'flex' : 'none',
    alignItems: 'center',
    marginTop: theme.spacing[3],
    ...(isMobile ? {} : { transition: `opacity ${theme.motion.duration.xgentle / 2}ms` }),
  }),
);

export const CarouselDataWidgetTextWrapper = styled.p<{ isMobile: boolean }>(
  ({ isMobile, theme }) => {
    const lineClamp = isMobile ? unTrimmedLines : trimmedLines;
    return {
      // TODO: Remove truncate logic once strings are updated as per new length
      WebkitLineClamp: lineClamp,
      color: theme.colors.surface.text.gray.muted,
      display: ' -webkit-box',
      WebkitBoxOrient: 'vertical',
      overflow: 'hidden',
    };
  },
);

export const CarouselDataWidgetWrapper = styled.div<{ isMobile: boolean; hasAction: boolean }>(
  ({ theme, isMobile, hasAction }) => ({
    display: 'flex',
    backgroundColor: theme.colors.surface.background.gray.subtle,
    borderRadius: theme.border.radius.medium,
    padding: theme.spacing[5],
    height: isMobile ? 120 : 100,
    width: 320,
    gap: theme.spacing[4],
    cursor: hasAction ? 'pointer' : 'unset',

    ...(isMobile
      ? {}
      : {
          [`&:hover ${CarouselDataWidgetTitleWrapper}`]: {
            opacity: 0,
            maxHeight: 0,
          },

          [`&:hover ${CarouselDataWidgetActionWrapper}`]: {
            opacity: 1,
            display: 'flex',
          },

          [`&:hover ${CarouselDataWidgetTextWrapper}`]: {
            WebkitLineClamp: unTrimmedLines,
          },
        }),
  }),
);
