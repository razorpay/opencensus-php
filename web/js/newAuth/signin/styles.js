/* eslint-disable */
import Styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import { BANK_NAMES } from '../utils';
import { media } from '../breakpoints';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';

export const Container = Styled(View)`
  overflow-y: auto;
  background: ${({ org }) =>
    ((org === BANK_NAMES.ICICI || org === BANK_NAMES.AXIS) && theme.colors.background[600]) ||
    'linear-gradient(0deg, rgba(2, 42, 156, 0.3), rgba(2, 42, 156, 0.3)), linear-gradient(247.07deg, #020529 -52%, #000B8E 198.1%);'};
`;

export const CustomLinkButton = Styled(View)`
  display: inline-block;
  padding: 0;
  text-decoration: none;
  font-weight: 600;
  font-size: 16px;
  color: #528ff0;
  font-family: Lato-Bold;
  cursor: pointer;
  &&:visited {
    background-color: transparent;
  }
  &&:hover  {
    background-color: transparent;
    color: #135fd9;
    & > span {
      transform: translateX(3px);
      color: #135fd9;
    }
  }
  &&:active {
    background-color: transparent;
  }
  & > span {
    transition: .12s ease-in;
    margin-left: 4px;
    display: inline-block;
    font-family: Muli,BlinkMacSystemFont,-apple-system,"Segoe UI",Roboto,Oxygen,Ubuntu,Cantarell,"Fira Sans","Droid Sans","Helvetica Neue",Helvetica,Arial,sans-serif;
  }
`;

export const AbsoluteView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  border-radius: 2px;
  margin: 0 auto;
  flex-grow: 1;
  height: 100%;
  max-width: 100%;
  background-color: ${({ theme }) => theme.colors.background[100]};
  @media ${media.mobile} {
    height: 577px;
    max-width: 375px;
  }
  @media ${media.tab} {
    flex-grow: initial;
    position: absolute;
    right: 32px;
    top: -30px;
    width: 320px;
    height: calc(100% + 58px);
  }
`;

export const RelativeView = Styled(View)`
  box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.05);
  height: calc(100% - 73px);
  border-radius: 4px;
  display: flex;
  flex-grow: 1;

  @media ${media.mobile} {
    height: auto;
    margin-bottom: 30px;
  }
  @media ${media.tab} {
    height: 540px;
    flex-grow: initial;
    display: block;
    position: relative;
    background: ${({ theme }) => theme.colors.background[400]};
    padding-right: 384px;
    margin: 32px 0;
  }
`;

export const Image = Styled.img`
  width: 100%;
`;

export const ContentContainer = Styled(View)`
  margin: 0 auto;
  height: 100%;
  @media ${media.mobile} {
    display: block;
    padding: 0;
  }
  @media ${media.tabBig} {
    padding: 0 32px;
  }
   @media ${media.desktop} {
    padding: 0;
  }

`;

export const ContactUsLinkButton = Styled(View)`
  display: inline-block;
  text-decoration: none;
`;
