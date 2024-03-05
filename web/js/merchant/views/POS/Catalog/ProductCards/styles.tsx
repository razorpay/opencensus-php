import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const ProductCardsContainer = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: 100%;
    display: block;
    gap: ${theme.spacing[5]}px;

    @media screen and (min-width: ${theme.breakpoints.xl + 100}px) {
      display: flex;
    }
  `,
);

export const AndroidSmartMiniPosContainer = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
    width: 100%;
    min-width: 100px;
    height: 400px;    
    background-color: ${theme.colors.surface.background.level1.lowContrast};
    position: relative;
    border-radius: ${theme.border.radius.large}px;
    overflow: hidden;
    box-shadow: ${isHovered ? theme.elevation.highRaised : 'none'};
    transition: box-shadow ease 0.5s;
    margin-bottom: ${theme.spacing[5]}px;
    display:flex;
    justify-content: center;
  `,
);

export const ProductCardLeftImageAnimate = styled.div(
  ({ isHovered, isOfferExits }: { isHovered: boolean; isOfferExits: boolean }) => `
  max-width: 540px;
  min-width: 500px;
  position: relative;
  transform: scale(${isHovered ? '1.03' : '1'});
  transition: transform ease 0.5s;
  transform-origin: bottom left;
  position: absolute;
  bottom: ${isOfferExits ? '-5px' : '0px'};
  left: 0;
  `,
);

export const AndroidPosMiniEllipse1 = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: 120%;
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background-color: ${theme.colors.brand.primary[400]};
    filter: blur(100px);
    right: 40%;
    top: 0;
    position: absolute;
  `,
);

export const AndroidPosMiniEllipse2 = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
    width: 50%;
    max-width: 550px;
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background: ${theme.colors.surface.background.level2.lowContrast};
    filter: blur(75px);
    right: 80%;
    bottom: 45%;
    position: absolute;
    z-index: 1;
    opacity: ${isHovered ? 1 : 0}; 
    transition: opacity ease 0.5s;
  `,
);

export const AndroidPosMiniEllipse3 = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
    width: 90%;
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background: ${theme.colors.surface.background.level2.lowContrast};
    position: absolute;
    z-index: 1;
    left: 60%;
    filter: blur(140px);
    opacity: ${isHovered ? 1 : 0}; 
    top: 10%;
    transition: opacity ease 0.5s;
  `,
);

export const MobilePosContainer = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
    width: 100%;
    min-width: 330px;
    height: 400px;    
    background-color: ${theme.colors.surface.background.level1.lowContrast};
    position: relative;
    border-radius: ${theme.border.radius.large}px;
    overflow: hidden;
    box-shadow: none;
    box-shadow: ${isHovered ? theme.elevation.highRaised : 'none'};
    transition: box-shadow ease 0.5s;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: ${theme.spacing[5]}px;
  `,
);

export const MobilePosCardImage = styled.div(
  ({ isHovered }: { isHovered: boolean }) => `
    position: absolute;
    z-index:2;
    height: 85%;
    width: 100%;
    left: 40%;
    bottom: 4%;
    transform: scale(${isHovered ? 1.04 : 1});
    transition: transform ease 0.5s;
    transform-origin: bottom right;
  `,
);

export const MobilePosCardEllipse1 = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: 80%;
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background: linear-gradient(180deg, rgba(21, 102, 241, 0.00) 9.07%, rgba(21, 102, 241, 0.18) 99.94%);
    filter: blur(50px);
    right: 0%;
    bottom: 20%;
    position: absolute;
  `,
);

export const MobilePosCardEllipse2 = styled.div(
  ({ theme, isHovered }: { theme: Theme; isHovered: boolean }) => `
    width: 80%;
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background: ${theme.colors.surface.background.level2.lowContrast};
    filter: blur(75px);
    right: 70%;
    top: 5%;
    position: absolute;
    z-index: 1;
    opacity: ${isHovered ? 1 : 0}; 
    transition: opacity ease 0.5s;
  `,
);

export const StyledProductCardImage = styled.img`
  max-inline-size: 100%;
  height: 100%;
`;

export const MobileEllpise = styled.div(
  ({ theme }: { theme: Theme }) => `
  width: 80%;
  border-radius: ${theme.border.radius.round};
  background: ${theme.colors.brand.primary[400]};
  filter: blur(200px);
  left: 40%;
  right: 0%;
  bottom: 20%;
  height: 100%;
  position: absolute;
  transform: rotate(7.869deg);
  z-index:0;
  `,
);
