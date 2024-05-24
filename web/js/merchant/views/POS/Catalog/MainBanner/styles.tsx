import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import { MainBannerItemStyleProps } from 'merchant/views/POS/types';

export const StyledPriceTagImage = styled.img`
  max-inline-size: 100%;
  position: relative;
  left: 4px;
  z-index: -1;
`;

export const AndroidPosPriceBadgeImage = styled.div`
  position: absolute;
  z-index: 1;
  bottom: 10%;
  width: 45%;
  min-width: 100px;
  right: 180px;
`;

export const MainBannerFeaturesContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
      margin-top: ${theme.spacing[6]}px;
      display: block;
      @media screen and (min-width: ${theme.breakpoints.xl}px) {
        display: grid;
        grid-template-columns: 1fr 1fr;
      }
    `,
);

export const MainBannerFooter = styled.section(
  ({ theme }: { theme: Theme }) => `
    margin-top: ${theme.spacing[11]}px;
    bottom: 0;
  `,
);

export const MainBannerTitleContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    width:100%;
    min-width: 120px;
    max-height: 140px;
    min-height: 90px;
    border: ${theme.border.width.thinner}px solid ${theme.colors.surface.border.gray.normal};
    border-radius: ${theme.border.radius.large}px;
    margin-bottom:${theme.spacing[6]}px;
    overflow: hidden;
    position: relative;
    background-color: ${theme.colors.surface.background.gray.subtle};
  `,
);

export const MainBannerTileOverlay = styled.div(
  ({ isHovered }: { isHovered: boolean }) => `
  height: 100%;
  width: 100%;
  background: linear-gradient(180deg, rgba(13, 30, 58, 0.00) -43.68%, #11284F 100%);
  opacity: ${isHovered ? 1 : 0};
  transition: opacity 0.2s ease-in-out;
  z-index: 1;
  position: relative;
  `,
);

export const MainBannerTileFooter = styled.div(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  bottom:0;
  left:0;
  right: 0;
  padding: ${theme.spacing[0]} ${theme.spacing[2]}px;
  z-index: 1;
  .text-container{
    transform: translateY(100px);
    transition: transform 0.2s ease-in-out;
    margin-bottom: ${theme.spacing[3]}px;
  }
  `,
);

export const MainBannerTileText = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
  transform: translateY(${isHovered ? '0' : '100px'});
  transition: transform 0.2s ease-in-out;
  margin-bottom: ${theme.spacing[3]}px;
  `,
);

export const MainBannerTileImage = styled.div(
  ({ isHovered, imageStyles }: { isHovered: boolean; imageStyles: MainBannerItemStyleProps }) => `
  height: 100%;
  width: 100%;
  position: absolute;
  top: ${imageStyles.top};
  transform: scale(${isHovered ? imageStyles.finalZoom : imageStyles.initialZoom});
  transition: transform 0.2s ease-in-out;
  z-index:0;
  >img{
    max-inline-size: 100%;
    height: 100%;
    width: 100%;
    object-fit: cover;
   }
  `,
);

export const StyledMainBannerImage = styled.img`
  max-width: 230px;
  min-width: 150px;
  max-height: 500px;
  height: 100%;
`;
