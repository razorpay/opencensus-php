import styled from 'styled-components';
import Text from '@razorpay/blade-old/src/atoms/Text';
import View from '@razorpay/blade-old/src/atoms/View';
import Link from '@razorpay/commander-shield/src/shared/Link';

export const Header = styled(Text)`
  padding: 100px;
  font-size: 40px;
  line-height: 52px;
  @media (max-width: 768px) {
    font-size: 36px;
    padding: 80px;
  }
  @media (max-width: 440px) {
    font-size: 24px;
    padding: 36px 10px;
    line-height: 30px;
  }
`;

export const HeaderWrapper = styled(View)`
  position: relative;
  background: linear-gradient(40.46deg, #0041b1 -21.46%, #7edeff 120.64%);

  &:after {
    content: '';
    width: 100%;
    height: 60px;
    position: absolute;
    transform: skew(0deg, 358deg);
    bottom: -30px;
    background: ${({ unAuthorizePage }) => (unAuthorizePage ? '#fff' : '#f0f3f4')};
  }
  @media (max-width: 440px) {
    &:after {
      height: 30px;
      bottom: -8px;
    }
  }
`;

export const Wrapper = styled(View)`
  padding: 36px 180px;
  @media (max-width: 1024px) {
    padding: 36px 120px;
  }
  @media (max-width: 768px) {
    padding: 24px 90px 36px;
  }
  @media (max-width: 440px) {
    padding: 10px 32px 36px;
  }
`;

export const UnderLine = styled(View)`
  border: 2px solid #74d6b7;
  margin: 12px 0px 24px;
  @media (max-width: 440px) {
    margin: 8px 0px 13px;
  }
`;

export const Footer = styled(View)`
  background: linear-gradient(36.44deg, #0041b1 -19.49%, #7edeff 109.57%);
  padding: 65px 205px;
  @media (max-width: 1024px) {
    padding: 55px 30px;
  }
  @media (max-width: 440px) {
    padding: 55px 0px;
  }
`;

export const FooterText = styled(Text)`
  font-size: 28px;
  width: 76%;
  margin: 0 auto;
  @media (max-width: 440px) {
    font-size: 18px;
    line-height: 24px;
  }
`;

export const FooterSeprator = styled(View)`
  width: 65px;
  border: 3px solid #74d6b7;
  background: #74d6b7;
  margin: 0 auto 20px;
  @media (max-width: 768px) {
    margin: 0 auto 8px;
  }
`;

export const InLineText = styled(Text)`
  display: inline;
  @media (max-width: 440px) {
    font-size: 12px;
  }
`;

export const ContentWrapper = styled(View)`
  margin-top: 64px;
  @media (max-width: 768px) {
    margin-top: 40px;
  }
  @media (max-width: 440px) {
    margin-top: 30px;
  }
`;

export const List = styled.li`
  font-size: 20px;
  color: #0d2462;
  @media (max-width: 440px) {
    font-size: 18px;
  }
`;

export const HeaderSeprator = styled(View)`
  width: 65px;
  border: 3px solid #74d6b7;
  background: #74d6b7;
  margin: 16px auto 0;
  @media (max-width: 440px) {
    margin: 8px auto 10px;
    border: 2px solid #74d6b7;
  }
`;

export const Title = styled(Text)`
  @media (max-width: 768px) {
    font-size: 30px;
  }
  @media (max-width: 440px) {
    font-size: 20px;
  }
`;

export const SubText = styled(Text)`
  margin: 64px 0px 24px;
  @media (max-width: 768px) {
    margin: 36px 0px 18px;
  }
  @media (max-width: 440px) {
    margin: 18px 0px 8px;
    font-size: 14px;
  }
`;

export const SubTitle = styled(Text)`
  font-size: 24px;
  @media (max-width: 768px) {
    font-size: 18px;
    line-height: 30px;
  }
`;

export const Description = styled(Text)`
  @media (max-width: 440px) {
    font-size: 12px;
  }
`;

export const TnCLink = styled(Link)`
  word-break: break-word;
  font-size: 16px;
  @media (max-width: 440px) {
    font-size: 12px;
  }
`;
