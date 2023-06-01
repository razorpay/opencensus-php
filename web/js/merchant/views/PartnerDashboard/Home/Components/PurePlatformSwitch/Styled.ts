import styled from 'styled-components';
import { Theme } from '@razorpay/blade/components';
import managePaymentsBkg from 'assets/partner-dashboard/manage-payments-banner.svg';
import applicationFormImg from 'assets/partner-dashboard/application-form-img.svg';
import applicationReceivedImg from 'assets/partner-dashboard/application-received-img.svg';
import ppSwitchBanner from 'assets/partner-dashboard/pure-platform-banner-img.svg';
import haveAllCapabilitiesImg from 'assets/partner-dashboard/have-all-capabilities-img.svg';

const mobileTabMax = '1170px';

export const PPSwitchWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: flex;
  background: url(${ppSwitchBanner}) no-repeat center;
  background-size: cover;
  padding: ${theme.spacing[9]}px ${theme.spacing[9]}px;
  margin: 0px 22px;

  @media (max-width: ${mobileTabMax}) {
    display: block;
    padding: 25px 20px;
    background: #fff;
  }
`,
);

export const PPSwitchContent = styled.div(
  ({ theme }: { theme: Theme }) => `
    color: #34496C;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[400]}px;
    line-height: ${theme.typography.lineHeights[400]}px;
    width: 553px;

    .pp-title-highlight {
      color: #FFBD3B;
    }

    @media (max-width:${mobileTabMax}) {
      width: auto;
    }
  `,
);

export const PPCTAWrap = styled.div`
  height: fit-content;
  margin-top: 42px;
  margin-left: 25px;

  @media (max-width: ${mobileTabMax}) {
    margin-left: 0px;
  }
`;

export const PPSwitchContentTitle = styled.div`
  color: #34496c;
  margin-bottom: 14px;

  .pp-title-highlight {
    color: #ffbd3b;
  }
`;

export const PPSwitchContentDesc = styled.div(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.subtle.lowContrast};
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[100]}px;
  line-height: ${theme.typography.lineHeights[100]}px;

  a {
    color: #0B70E7;
  }
`,
);

export const MainContentWrap = styled.div(
  ({ width }: { width: string }) => `
  width: ${width};
  padding-left: 52px;

  @media (max-width: ${mobileTabMax}) {
    padding: 0px;
    width: 100%;
  }
`,
);

export const ApplicationFlowWrapper = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 1100px;
  height: 600px;
  padding: 55px 55px 90px;

  @media (max-width: ${mobileTabMax}) {
    padding: 0px ${theme.spacing[7]}px 0px 0px;
    width: 100%;
  }
`,
);

export const HeaderWrapper = styled.div`
  display: flex;
  justify-content: space-between;

  @media (max-width: ${mobileTabMax}) {
    display: none;
  }
`;

export const HeaderBlock = styled.div(
  ({ theme }: { theme: Theme }) => `
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[200]}px;
  line-height: ${theme.typography.lineHeights[200]}px;
  color: #7d889a;

  .icon-block-left {
    margin-right: ${theme.spacing[6]}px;
  }
  .icon-block {
    vertical-align: middle;
    cursor: pointer;
    margin-left: 10px;
  }
  .service-provided {
    visibility: hidden;
  }
`,
);

export const HeaderBlockRight = styled.div(
  ({ theme }: { theme: Theme }) => `
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[200]}px;
    line-height: ${theme.typography.lineHeights[200]}px;
    color: #7d889a;
    width: 40%;
    display: flex;
    align-items: center;
    justify-content: end;

    .progress-wrap {
      width: 11%;
      margin-right: 15px;
    }

    .icon-block {
      vertical-align: middle;
      cursor: pointer;
      margin-left: 10px;
    }
`,
);

export const ServiceProvidedHeading = styled.div(
  ({ theme }: { theme: Theme }) => `
    margin-top: 36px;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[700]}px;
    line-height: ${theme.typography.lineHeights[700]}px;
    color: #324664;

    @media (max-width: ${mobileTabMax}) {
      margin: 0;
      font-size: ${theme.typography.fonts.size[200]}px;

      .mobile-header-content {
        margin-left: 10px;
        vertical-align: bottom;
      }

      .mobile-header-close {
        right: 5%;
        top: 5.5%;
        position: absolute;
      }
    }
  `,
);

export const ServiceProvidedDescription = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-top: 4px;
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[200]}px;
  line-height: ${theme.typography.lineHeights[200]}px;
  color: rgba(33, 53, 84, 0.67);
  width: 55%;

  @media (max-width:${mobileTabMax}) {
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: ${theme.typography.lineHeights[100]}px;
    width: 100%;
    color: ${theme.colors.surface.text.subdued.lowContrast};
  }
`,
);

export const ServiceProvidedPills = styled.div`
  display: flex;
  flex-wrap: wrap;
  gap: 14px 14px;
  margin-top: 51px;
  width: 80%;

  @media (max-width: ${mobileTabMax}) {
    width: 100%;
  }
`;

export const ServiceProvidedPillsOption = styled.div(
  ({ theme, $isActive }: { theme: Theme; $isActive: boolean }) => `
    padding: 7px 10px;
    border: 1.42829px solid #8895A8;
    border-radius: 11.4263px;
    cursor: pointer;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[100]}px;
    line-height: ${theme.typography.lineHeights[100]}px;

    :hover {
      color: ${theme.colors.brand.primary[600]};
      border-color: ${theme.colors.brand.primary[600]};
    }
    ${
      $isActive
        ? `
        color: ${theme.colors.brand.primary[600]};
        border-color: ${theme.colors.brand.primary[600]};
      `
        : ''
    }
`,
);

export const ServiceProviderFooter = styled.div`
  width: 94%;
  margin-top: 25px;

  button {
    position: absolute;
    width: 95px;
    right: 90px;
    bottom: 56px;
  }

  @media (max-width: ${mobileTabMax}) {
    width: 100%;

    button {
      position: absolute;
      bottom: 0px;
      left: -25px;
      right: 0px;
      width: 85%;
      margin: 25px auto;
    }
  }
`;

export const ServiceProvidedOtherText = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 45%;
  margin-top: ${theme.spacing[6]}px;

  @media (max-width: ${mobileTabMax}) {
    width: 100%;
  }
`,
);

export const ManagePaymentsBanner = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: url(${managePaymentsBkg});
  display: flex;
  justify-content: space-between;
  padding: 25px 30px;
  margin-top: 58px;
  margin-bottom: ${theme.spacing[7]}px;

  @media (max-width: ${mobileTabMax}) {
    margin-top: 40px;
    display: block;
  }
`,
);

export const ManagePaymentHeading = styled.div(
  ({ theme }: { theme: Theme }) => `
  color: ${theme.colors.surface.text.subtle.lowContrast};
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[400]}px;
  line-height: ${theme.typography.lineHeights[400]}px;

  @media (max-width:${mobileTabMax}) {
    font-size: ${theme.typography.fonts.size[200]}px;
    line-height: ${theme.typography.lineHeights[200]}px;
    color: ${theme.colors.surface.background.level3.highContrast};
    margin-bottom: 20px;
  }
`,
);

export const ManagePaymentButtonWrap = styled.div`
  button {
    margin-left: 20px;

    @media (max-width: ${mobileTabMax}) {
      margin-left: 0px;
      margin-right: 20px;
    }
  }
`;

export const IntegrateAPIBanner = styled.div(
  ({ theme }: { theme: Theme }) => `
  background: ${theme.colors.feedback.background.neutral.lowContrast};
  padding: 25px 30px;
  position: relative;
`,
);

export const IntegrateAPIHeading = styled.div(
  ({ theme }: { theme: Theme }) => `
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[200]}px;
  line-height: ${theme.typography.lineHeights[200]}px;
  color: ${theme.colors.surface.text.subtle.lowContrast};
  margin-bottom: 8px;

  span {
    vertical-align: text-bottom;
    margin-left: 12px;
  }
`,
);

export const IntegrateAPIDesc = styled.div(
  ({ theme }: { theme: Theme }) => `
  font-weight: ${theme.typography.fonts.weight.regular};
  font-size: ${theme.typography.fonts.size[100]}px;
  line-height: ${theme.typography.lineHeights[100]}px;
  color: ${theme.colors.surface.text.subdued.lowContrast};

  @media (max-width:${mobileTabMax}) {
    width: 100%;
  }
`,
);

export const IntegrateAPICTA = styled.div(
  ({ theme }: { theme: Theme }) => `
  cursor: pointer;
  display: inline-flex;
  position: absolute;
  right: 35px;
  top: 50px;

  span {
    vertical-align: text-bottom;
    margin-right: 8px;
    font-weight: ${theme.typography.fonts.weight.bold};
    font-size: ${theme.typography.fonts.size[75]}px;
    line-height: ${theme.typography.lineHeights[75]}px;
    color: #2b83ea;
  }

  @media (max-width: ${mobileTabMax}) {
    position: unset;
    margin-top: 12px;
  }
`,
);

export const ApplicationFormContent = styled.div`
  margin-top: 20px;
`;

export const ApplicationFormWrapper = styled.div(
  ({ width = '55%' }: { width?: string }) => `
  margin-top: 20px;
  width: ${width};

  @media (max-width: ${mobileTabMax}) {
    width: 100%;
  }
`,
);

export const ApplicationFormImg = styled.div`
  margin-top: 20px;
  background: url(${applicationFormImg}) no-repeat;
  width: 400px;
  height: 400px;
  position: absolute;
  right: 4%;
  top: 20%;

  @media (max-width: ${mobileTabMax}) {
    display: none;
  }
`;

export const ApplicationFooter = styled.div`
  width: 60%;
  margin-top: 25px;

  .btn-wrap {
    button {
      position: absolute;
      width: 95px;
      right: 48%;
      bottom: 6%;

      @media (max-width: ${mobileTabMax}) {
        position: unset;
        width: 100%;
      }
    }
  }

  .btn-wrap-big {
    button {
      width: 165px;
      bottom: 10%;

      @media (max-width: ${mobileTabMax}) {
        position: unset;
        width: 100%;
      }
    }
  }

  @media (max-width: ${mobileTabMax}) {
    position: absolute;
    bottom: 0px;
    left: 0px;
    right: 0px;
    width: 85%;
    margin: 25px 15px;
  }
`;

export const ApplicationReceivedImg = styled.div`
  margin-top: 20px;
  background: url(${applicationReceivedImg}) no-repeat;
  width: 400px;
  height: 400px;
  position: absolute;
  right: 2%;
  top: 25%;

  @media (max-width: ${mobileTabMax}) {
    display: none;
  }
`;

export const ApplicationStepWrapper = styled.div`
  display: flex;
  margin-bottom: 32px;
  margin-top: 40px;
  position: relative;
`;

export const ApplicationDotContent = styled.div(
  ({ theme }: { theme: Theme }) => `
  margin-left: 30px;
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[100]}px;
  line-height: ${theme.typography.lineHeights[100]}px;
  color: ${theme.colors.surface.background.level3.highContrast};

  .pp-dot-subtitle {
    color: #9D9D9D;
    font-weight: ${theme.typography.fonts.weight.regular};
  }

  ul {
    position: absolute;
    left: -12px;
    top: 35px;
    color: #D9D9D9;
    font-size: 16px;
  }
`,
);

export const MobileHeaderWrapper = styled.div`
  display: flex;
  justify-content: space-between;
`;

export const MobileHeaderContent = styled.div`
  position: relative;
`;

export const MobileHeaderCloseIcon = styled.span`
  margin-right: -12px;
  margin-top: 4px;
`;

export const HaveAllCapabilitiesImg = styled.div`
  margin-top: 20px;
  background: url(${haveAllCapabilitiesImg}) no-repeat;
  width: 400px;
  height: 400px;
  position: absolute;
  right: 0%;
  top: 30%;

  @media (max-width: ${mobileTabMax}) {
    display: none;
  }
`;

export const HaveAllCapabilitiesTitle = styled.div(
  ({ theme }: { theme: Theme }) => `
  font-weight: ${theme.typography.fonts.weight.bold};
  font-size: ${theme.typography.fonts.size[400]}px;
  line-height: ${theme.typography.lineHeights[400]}px;
  color: #324664;

  @media (max-width: ${mobileTabMax}) {
    font-size: ${theme.typography.fonts.size[200]}px;
  }
`,
);
