import styled from 'styled-components';
import { makeSize } from '@razorpay/blade/utils';

export const StyledContainer = styled.div(
  ({ theme }) => `
    .sr-metrics {
      .nav.nav-tabs {
        ::-webkit-scrollbar {
          height: ${makeSize(8)};
          cursor: pointer;
        }
        ::-webkit-scrollbar-track {
          background: ${theme.colors.interactive.background.gray.default};
          border-radius: ${theme.border.radius.small}px;
          cursor: pointer;
        }
        ::-webkit-scrollbar-thumb {
          background: ${theme.colors.overlay.background.moderate};
          border-radius:  ${theme.border.radius.large}px;
          cursor: pointer;
        }
        :hover::-webkit-scrollbar-thumb {
          background: ${theme.colors.surface.border.gray.normal};
          border-radius: ${theme.border.radius.large}px;
          cursor: pointer;
        }
      }
      .nav.nav-tabs > li {
        background-color: red;
        @media screen and (min-width: ${makeSize(1300)}) {
          min-width: 17vw;
        }
        @media screen and (max-width: ${makeSize(1300)}) {
          min-width: 18vw;
        }
        @media screen and (max-width: ${theme.breakpoints.l}px) {
          min-width: ${makeSize(250)};
        }
      }
      .tab-content {
        margin-top: ${theme.spacing[5]}px;
      }
    } 
  `,
);

export const SuccessRateDateFilterContainer = styled.div`
  display: flex;
  align-items: center;
  > div:first-child {
    min-width: 10rem;
  }
  > div :nth-child(2) {
    margin-left: ${({ theme }) => theme.spacing[2]}px;
  }
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.xl}px) {
    display: block;
    > div :nth-child(2) {
      margin: ${({ theme }) => theme.spacing[5]}px 0 0 0;
    }
  }
  @media screen and (max-width: ${({ theme }) => theme.breakpoints.m}px) {
    width: 100%;
  }
`;
