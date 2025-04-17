import { Theme } from '@razorpay/blade/components';

import styled from 'styled-components';
import RzpDesktopBanner from 'assets/razorpay_rewind/desktop-banner.png';
import RzpMobileBanner from 'assets/razorpay_rewind/mobile-banner.png';
import RzpTabletBanner from 'assets/razorpay_rewind/tablet-banner.png';
import { RewindFontMapping, RewindFonts, SnugSharpFontVariants } from './RazorpayRewind';

export const CarouselContainer = styled.div`
  background-color: #000223;
`;

export const PaymentsRecapContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
  background-color: #000223;
  padding: ${theme.spacing[5]}px ${theme.spacing[7]}px;
  padding-top: ${theme.spacing[10]}px;
  border-radius: ${theme.spacing[3]}px;
`,
);

export const StyledTextHighlight = styled.span<{ fontSize?: string }>(
  ({ theme, fontSize }) => `
    font-weight: ${theme.typography.fonts.weight.semibold};
    font-style: italic;
    color: ${theme.colors.primary};
    font-size: ${fontSize || `${theme.typography.fonts.size[200]}px`};
    line-height: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  `,
);

export const StyledTextHeading = styled.p<{ fontSize?: string; color?: string }>(
  ({ theme, fontSize, color }) => `
  font-weight: ${theme.typography.fonts.weight.semibold};
  font-style: italic;
  color: ${color || '#c1ff84'};
  font-size: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  line-height: ${fontSize || `${theme.typography.fonts.size[200]}px`};
  text-align: center;
  `,
);

export const SocialShareBottomSheet = styled.div(
  (_) => `
  [data-blade-component='bottom-sheet'] {
    background-color: rgb(28,40,56);
  }
  [data-testid='bottomsheet-backdrop']{
    background-color: transparent;
  }
`,
);

export const StyledIcon = styled.div(
  ({ theme }: { theme: Theme }) => `
  display: grid;
  place-items: center;
  background-color: #FFFFFF;
  height: ${theme.spacing[10]}px;
  width: ${theme.spacing[10]}px;
  border-radius: ${theme.spacing[2]}px;
  cursor: pointer;
`,
);

export const BannerText = styled.div<{ isMobileorTabletModified?: boolean }>`
  color: white;
  font-family: 'TASA Orbiter Display', sans-serif;
  font-size: ${({ isMobileorTabletModified }) => (isMobileorTabletModified ? '16px' : '26px')};
  width: ${({ isMobileorTabletModified }) => (!isMobileorTabletModified ? '300px' : undefined)};
  line-height: 108%;
`;

export const BannerBtn = styled.button<{ isMobileBanner: boolean }>(
  ({ theme }) => `
  font-size: ${theme.typography.fonts.size[100]}px;
  min-height: 36px;
  padding: ${theme.spacing[0]}px ${theme.spacing[5]}px;
  width: max-content;
  cursor: pointer;
  background-color: #c1ff84;
  color: #2d2a26;
  font-weight: ${theme.typography.fonts.weight.medium};
  border-radius: 6px;
  font-style: italic;
  border-width: ${theme.spacing[0]}px;
  `,
);

export const TextWrapper = styled.p<{
  isMobileBanner?: boolean;
  top?: string;
  left?: string;
  right?: string;
  fontSize?: string;
  fontWeight?: string;
  color?: string;
  textAlign?: string;
  fontFamily?: RewindFonts;
}>(({ theme, top, left, right, fontSize, fontWeight, color, textAlign, fontFamily }) => ({
  alignItems: 'center',
  // fontStyle: 'italic',
  fontSize: fontSize || '80px',
  fontWeight: fontWeight || 800,
  color: color || '#A0F150',
  position: 'absolute',
  zIndex: 1,
  left: left || '80px',
  top: top || '170px',
  right: right || `${theme.spacing[0]}px`,
  whiteSpace: 'pre-line',
  textAlign: textAlign || undefined,
  fontFamily: RewindFontMapping[fontFamily] || undefined,
}));

export const BestTimeContainer = styled.p<{
  fontSize?: string;
}>(
  ({ theme, fontSize }) => `
  font-size: ${fontSize || '10px'};
  font-weight: ${theme.typography.fonts.weight.regular};
  color: #b4cdfd;
  display: flex;
  flex-direction: row;
  align-items: center;
  `,
);

export const BestTimePercentage = styled.div<{
  isMobileBanner?: boolean;
  width?: string;
  isBestTime: boolean;
  height?: string;
}>(
  ({ theme, isBestTime, width, height }) => `
  background-color: ${isBestTime ? '#B4CDFD' : 'transparent'};
  height: ${height || '10px'};
  border-radius: 14px;
  width: ${width || `${theme.spacing[0]}px`};
  margin-right: 5px;
  border: ${isBestTime ? `${theme.spacing[0]}px` : '1px solid #B4CDFD'};
  `,
);

export const BannerWrapper = styled.div<{
  isMobileorTablet?: boolean;
  isMobile?: boolean;
  isTablet?: boolean;
}>`
  display: flex;
  flex-direction: ${({ isMobileorTablet }) => (isMobileorTablet ? 'column' : 'row')};
  align-items: center;
  justify-content: space-around;
  gap: ${({ isMobileorTablet, theme }) =>
    isMobileorTablet ? theme.spacing[3] : theme.spacing[7]}px;
  padding: ${({ theme }) => theme.spacing[4]}px;
  background-image: ${({ isMobile, isTablet }) =>
    `url("${isMobile ? RzpMobileBanner : isTablet ? RzpTabletBanner : RzpDesktopBanner}")`};
  background-repeat: no-repeat;
  background-size: cover;
  background-position: left top;
  border-radius: ${({ theme }) => theme.border.radius.medium}px;
  padding-left: ${({ theme, isMobileorTablet }) =>
    isMobileorTablet ? undefined : `${theme.spacing[11]}px`};
  margin-inline: ${({ isMobileorTablet, theme }) =>
    isMobileorTablet ? theme.spacing[5] : theme.spacing[6]}px;
`;

export const Milestone = styled.div`
  position: absolute;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: ${({ isMobile, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '5px' : '20px'};
  ${({ position }) => position}
`;

export const MilestoneDate = styled.div`
  background-color: #a0f150;
  color: #305eff;
  font-family: ${RewindFontMapping[RewindFonts.DMSerif]};
  font-style: italic;
  padding: 5px;
  padding-inline: ${({ isMobile }) => (isMobile ? '7px' : '10px')};
  border-radius: 25px;
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '0.6rem' : isTablet ? '0.7rem' : '0.85rem'};
  line-height: 1;
  width: fit-content;
`;

export const MilestoneData = styled.div`
  display: flex;
  flex-direction: column;
`;

export const MilestoneUpperText = styled.div`
  color: #fff;
  font-family: ${RewindFontMapping[RewindFonts.TasaOrbiter]};
  font-weight: 500;
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '0.6rem' : isTablet ? '0.7rem' : '0.85rem'};
  align-self: ${({ isMobile }) => (isMobile ? 'flex-start' : 'center')};
  line-height: 1;
`;

export const MilestoneLowerText = styled.div`
  color: #fff;
  font-family: ${RewindFontMapping[RewindFonts.TasaOrbiter]};
  font-weight: 500;
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '0.6rem' : isTablet ? '0.7rem' : '0.85rem'};
  line-height: 1;
  margin-bottom: ${({ isMobile }) => (isMobile ? '0.5rem' : '0')};
`;
export const MilestoneLabel = styled.div`
  color: #a0f150;
  font-family: ${RewindFontMapping[RewindFonts.SnugSharp]};
  line-height: 1;
  display: inline-flex;
  align-items: baseline;
`;

export const MilestoneRow = styled.div`
  display: flex;
  flex-direction: ${({ isMobile }) => (isMobile ? 'row' : 'column')};
  align-items: ${({ isMobile }) => (isMobile ? 'flex-end' : 'center')};
  justify-content: center;
`;

export const MilestoneLabelPrefix = styled.span`
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '1rem' : isTablet ? '1.2rem' : '1.5rem'};
  font-variation-settings: ${SnugSharpFontVariants.BOLD};
  margin: 0.3rem 0.2rem;
  align-self: flex-start;
`;

export const MilestoneLabelSuffix = styled.span`
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '2rem' : isTablet ? '2rem' : '2rem'};
  font-variation-settings: ${SnugSharpFontVariants.L_MEDIUM};
  margin: 0 0.2rem;
`;

export const MilestoneLabelValue = styled.span`
  font-size: ${({ isMobile, isTablet, shouldUseMobileFontSize }) =>
    isMobile || shouldUseMobileFontSize ? '3rem' : isTablet ? '3.5rem' : '4.25rem'};
  font-variation-settings: ${SnugSharpFontVariants.X_BOLD};
`;
