import styled from 'styled-components';
import View from '@razorpay/blade-old/src/atoms/View';

export const Wrapper = styled(View)`
  background: linear-gradient(99.96deg, #010315 3.48%, #01031c 24.68%, #010315 93.88%);
  display: flex;
  flex-direction: column;
  padding-top: 24px;
  padding-bottom: 96px;

  @media (min-width: 1025px) {
    padding-top: 70px;
    flex-direction: row;
    justify-content: center;
  }
`;

export const FeaturesImage = styled.img`
  width: 330px;
  margin-top: 8px;

  @media (min-width: 1025px) {
    width: 429px;
  }
`;

export const PayrollLogo = styled.img`
  width: 295px;
`;

export const LeftPanel = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 48px;
  position: relative;

  @media (min-width: 1025px) {
    margin-right: 108px;
  }
`;

export const MainTitle = styled.h1`
  font-weight: 800;
  color: #ffffff;
  font-size: 40px;
  line-height: 48px;
  margin: 0px;
`;

export const MainDescription = styled.div`
  font-weight: 400;
  font-size: 18px;
  color: #c7cfff;
  margin-top: 16px;
`;

export const RightPanel = styled.div`
  margin-top: 36px;
  padding: 0 24px;
  position: relative;

  @media (min-width: 1025px) {
    max-width: 610px;
    margin-top: 0px;
    padding: 0px;
  }
`;

export const Features = styled.div`
  display: grid;
  grid-template-columns: 1fr;
  column-gap: 64px;
  row-gap: 36px;
  margin-top: 48px;
  margin-bottom: 64px;

  @media (min-width: 1025px) {
    grid-template-columns: 1fr 1fr;
  }
`;

export const FeatureWrapper = styled.div``;

export const FeatureTitleWrapper = styled.div`
  display: flex;
`;

export const FeatureIcon = styled.i`
  margin-right: 6px;
  width: 15px;
  color: #3281ff;
  position: relative;
  top: 3px;
`;

export const FeatureTitle = styled.h4`
  margin: 0px;
  color: white;
  font-size: 16px;
`;

export const FeatureDescription = styled.div`
  font-size: 16px;
  color: #9396a9;
  margin-top: 10px;

  @media (min-width: 1025px) {
    font-size: 12px;
  }

  &::selection {
    color: white;
    background: rgba(109, 121, 208, 0.4);
  }
`;

export const PromotionWrapper = styled.div`
  background: url('/img/payroll/promotion_bg.png');
  background-size: contain;
  background-repeat: no-repeat;
  padding: 5px 50px 10px;
  text-align: center;
  position: absolute;
  bottom: 0px;
  left: 50%;
  transform: translateX(-50%);

  @media (min-width: 1025px) {
    bottom: 16px;
  }
`;

export const PromotionText = styled.div`
  color: #ffffff;
  font-size: 17px;
  font-weight: 600;
  text-align: center;
  width: 263px;
  line-height: 20px;
`;

export const PromotionTextHighlight = styled.span`
  color: #32ceff;
`;

export const TermsDesktop = styled.div`
  position: absolute;
  font-size: 10px;
  color: #9396a9;
  right: 0px;
  bottom: 0px;
  display: none;

  @media (min-width: 1025px) {
    display: block;
  }
`;

export const TermsMobile = styled.div`
  position: absolute;
  font-size: 10px;
  color: #9396a9;
  right: 0px;
  bottom: 0px;
  display: block;

  @media (min-width: 1025px) {
    display: none;
  }
`;
