/* eslint-disable */
import Styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';
import Flex from '@razorpay/blade-old/src/atoms/Flex';
import Text from '@razorpay/blade-old/src/atoms/Text';
import { lightTheme as theme } from '@razorpay/blade-old/src/tokens/theme.web';
import Button from 'newAuth/@deprecated/common/components/Button';
import { media } from './breakpoints';
const themeDarkBlue = theme.bladeOld.colors.highlight[700];
const themePrimary = theme.bladeOld.colors.primary[900];

export const HeaderView = Styled(View)`
  background: linear-gradient(149.39deg, ${themeDarkBlue} 0%, ${themePrimary} 100%);
  flex-shrink: 0;
  padding: 0;
  height: 73px;
  align-items: center;
  justify-content: center;
  @media ${media.mobile} {
    background: none;
    height: auto;
    padding: 48px 0 60px 0;
    justify-content: center;
    padding: 48px 42px 60px 42px;
  }
  @media ${media.tab} {
    justify-content: space-between;
    padding: 32px 0 36px 0;
  }
`;

export const DesktopOnlyView = Styled(View)`
  display: none;
  @media ${media.tab} {
    display: block;
  }
`;

export const MobileOnlyView = Styled(View)`
  display: none;
  @media (max-width: 768px) {
    display: block;
  }
`;

export const ContentContainer = Styled(View)`
  margin: 0 auto;
  @media ${media.mobile} {
    display: block;
  }
  @media ${media.tabBig} {
    padding: 0 32px;
  }
`;

export const CustomSecondaryButton = Styled(Button)`
  background-color: ${({ theme }) => theme.bladeOld.colors.background[100]};
  border: 1px solid ${({ theme }) => theme.bladeOld.colors.background[100]};
  > div{
   color: ${({ theme }) => theme.bladeOld.colors.primary[800]};
  }
  :hover, :focus{
   background-color: ${({ theme }) => theme.bladeOld.colors.background[800]};
   border: 1px solid ${({ theme }) => theme.bladeOld.colors.background[800]};
  }
  :active{
   background-color: ${({ theme }) => theme.bladeOld.colors.background[600]};
   border: 1px solid ${({ theme }) => theme.bladeOld.colors.background[600]};
   }
`;

export const FullHeightFlex = Styled(Flex)`
  height: 100%;
`;

export const InlineText = Styled(Text)`
  display: inline;
`;
