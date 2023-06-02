import React from 'react';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import PayrollLogoImage from 'assets/payroll/payroll-logo.png';
import PayrollFeaturesImage from 'assets/payroll/payroll-features.png';

import {
  Wrapper,
  FeaturesImage,
  PayrollLogo,
  LeftPanel,
  RightPanel,
  MainTitle,
  Features,
  MainDescription,
  FeatureWrapper,
  FeatureDescription,
  FeatureTitle,
  FeatureIcon,
  FeatureTitleWrapper,
  PromotionText,
  PromotionTextHighlight,
  PromotionWrapper,
  TermsDesktop,
  TermsMobile,
} from './styles';

import Button from 'common/new-ui/Button';

const Feature = ({ icon, title, description }) => (
  <FeatureWrapper>
    <FeatureTitleWrapper>
      <FeatureIcon className={`i i-${icon}`} />
      <FeatureTitle>{title}</FeatureTitle>
    </FeatureTitleWrapper>
    <FeatureDescription>{description}</FeatureDescription>
  </FeatureWrapper>
);

const Payroll = () => {
  const trackExploreClick = () => {
    analyticsTrack({
      objectName: 'Explore',
      actionName: 'clicked',
      screen: 'payroll page',
      properties: {
        location: 'payroll promotion',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  return (
    <Wrapper>
      <LeftPanel>
        <PayrollLogo src={PayrollLogoImage} alt="RazorpayX Payroll" />
        <FeaturesImage
          src={PayrollFeaturesImage}
          alt="Execute Payroll in minutes. Compliances on auto pilot."
        />
        <PromotionWrapper>
          <PromotionText>
            First <PromotionTextHighlight>1 month of free*</PromotionTextHighlight> Pro plan when
            you sign up
          </PromotionText>
        </PromotionWrapper>
        <TermsMobile>*T&C Apply</TermsMobile>
      </LeftPanel>
      <RightPanel>
        <MainTitle>3 Clicks, Payroll Fixed.</MainTitle>
        <MainDescription>
          Set your payroll and compliances like TDS, ESIC, PT, and PF on autopilot and save your
          precious time! No excel headache guaranteed.{' '}
        </MainDescription>
        <Features>
          <Feature
            icon="payroll-promo"
            title="1-click payroll processing"
            description="Employee salaries, freelancers and contractors’ payments"
          />
          <Feature
            icon="payroll-tax"
            title="Automated tax filing and payment"
            description="Automate TDS, PF, PT, and ESIC payments, filing and Form 16 generation"
          />
          <Feature
            icon="payroll-management"
            title="Easy employee management"
            description="Manage your employees right from onboarding to exit. Offer letter generator, CTC calculator, Salary Slip generator etc"
          />
          <Feature
            icon="payroll-insurance"
            title="Affordable group health insurance"
            description="Peace of mind guaranteed, even for teams as small as 2. Plans start at just ₹1350"
          />
          <Feature
            icon="payroll-rupee"
            title="Seamless flexible benefits"
            description="Help employees save up to ₹40,000 in tax with Flexible Benefit Plan"
          />
          <Feature
            icon="payroll-whatsapp"
            title="Reimbursements on WhatsApp"
            description="Employees get salary slips and file reimbursements - all on WhatsApp"
          />
        </Features>
        <a
          onClick={trackExploreClick}
          target="_blank"
          rel="noopener noreferrer"
          href="https://payroll.razorpay.com/sso?utm_source=payroll_widget&utm_medium=pgdashboard"
        >
          <Button.Primary>Explore Now</Button.Primary>
        </a>
        <TermsDesktop>*T&C Apply</TermsDesktop>
      </RightPanel>
    </Wrapper>
  );
};

export default Payroll;
