import styled from 'styled-components';
import bannerBGImage from 'assets/reports/overview-banner.png';
import bannerAccentImage from 'assets/reports/overview-accent.png';

export const OverViewBannerWrapper = styled.div(
  ({ theme }) => `
  background: #ffffff;
  border-bottom-left-radius: ${theme.border.radius.large}px;
  border-bottom-right-radius: ${theme.border.radius.large}px;
  position: relative;
  overflow: hidden;
  margin-bottom: ${theme.spacing[5]}px;
  min-height: 254px;
`,
);

export const BannerBGImage = styled.div(
  ({ theme }) => `
  width: 500px;
  height: 430px;
  position: absolute;
  top: ${theme.spacing[3]}px;
  right: -143px;
  background-size: contain !important;
  background: transparent url(${bannerBGImage}) 0 0 no-repeat;
  pointer-events: none;
  @media (max-width: ${theme.breakpoints.xl}px) {
    right: -240px;
  }
  @media (max-width: ${theme.breakpoints.l}px) {
    display: none;
  }
`,
);

export const BannerAccentImage = styled.div`
  width: 500px;
  height: 430px;
  position: absolute;
  top: -1px;
  left: -260px;
  background-size: contain !important;
  background: transparent url(${bannerAccentImage}) 0 0 no-repeat;
  pointer-events: none;
`;

export const OverViewContent = styled.div(
  ({ theme }) => `
  width: 65%;
  margin: 30px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  @media (max-width: ${theme.breakpoints.l}px) {
    width: 85%;
  }
  `,
);

export const ReportFeatureBtnWrapper = styled.div(
  ({ theme }) => `
  width: 200px;
  @media (max-width: ${theme.breakpoints.m}px) {
    margin-top: ${theme.spacing[4]}px;
  }
`,
);
