import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const MainContainer = styled.section(
  ({
    theme,
    isIgnorePadding,
    isIgnoreMarginBottom,
  }: {
    theme: Theme;
    isIgnorePadding?: boolean;
    isIgnoreMarginBottom?: boolean;
  }) => `
    display: block;
    background-color: ${theme.colors.surface.background.gray.intense};
    border: ${theme.border.width.thin}px solid ${theme.colors.interactive.border.gray.faded};
    border-top: ${theme.border.width.none};
    position: relative;
    padding: ${isIgnorePadding ? theme.spacing[0] : theme.spacing[7]}px;
    margin-bottom: ${isIgnoreMarginBottom ? theme.spacing[0] : theme.spacing[8]}px;

    @media screen and (max-width: ${theme.breakpoints.m}px) {
      padding: ${isIgnorePadding ? theme.spacing[0] : theme.spacing[4]}px;
    }
  `,
);

export const ProductWrapperStyled = styled.div`
  .pos-right-nav {
    padding: 0 !important;
  }
`;
