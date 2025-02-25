/* eslint-disable */
import Styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Link from '@libs/web-nexus/common/components/Link';
import { media } from '../breakpoints';

export const AbsoluteView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  border-radius: 2px;
  margin: 0 auto;
  flex-grow: 1;
  height: calc(100% - 62px);
  width: 100%;
  background-color: ${({ theme }) => theme.bladeOld.colors.background[100]};
  @media (min-width: 415px) {
    height: 577px;
    max-width: 375px;
  }
  @media (min-width: 769px) {
    flex-grow: initial;
    position: absolute;
    left: 32px;
    top: -48px;
    width: 320px;
    height: calc(100% + 96px);
  }
`;

export const RelativeView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  height: calc(100% - 73px);
  border-radius: 4px;
  display: flex;
  flex-grow: 1;
  flex-direction: column;
  @media ${media.mobile} {
    height: auto;
    margin-bottom: 30px;
  }
  @media ${media.tab} {
    height: 480px;
    flex-grow: initial;
    position: relative;
    background: ${({ theme }) => theme.bladeOld.colors.background[400]};
    padding-left: 384px;
    margin: 48px 0;
    justify-content: space-around;
  }
`;

export const Container = Styled(View)`
  height: 100%;
  overflow-y: auto;
  background: linear-gradient(0deg, rgba(2, 42, 156, 0.3), rgba(2, 42, 156, 0.3)), linear-gradient(232.85deg, #020529 -52%, #000B8E 198.1%);
`;

export const CustomLink = Styled(Link)`
  color: ${({ theme }) => theme.bladeOld.colors.shade[950]};
  text-decoration: underline;
  &&:visited {
    color: ${({ theme }) => theme.bladeOld.colors.shade[950]};
  }
  &&:hover {
    color: ${({ theme }) => theme.bladeOld.colors.shade[950]};
  }
  &&:active {
    color: ${({ theme }) => theme.bladeOld.colors.shade[950]};
  }
`;

export const BorderView = Styled(View)`
  width: 50px;
  height: 2px;
  position relative;
  left: 50%;
  margin-left: -25px;
  background: ${({ theme }) => theme.bladeOld.colors.shade[920]};
`;

export const DesktopBannerBg = Styled(View)`
  background: url(${require('assets/banner.svg')})
`;

export const MobileBannerBg = Styled(View)`
  background: rgba(65, 164, 19, 0.03);
`;

export const MobileBannerView = Styled(View)`
  display: none;
  @media (max-width: 767px) {
    display: block;
  }
`;

export const DesktopBannerView = Styled(View)`
  display: none;
  @media (min-width: 768px) {
    display: block;
  }
`;
