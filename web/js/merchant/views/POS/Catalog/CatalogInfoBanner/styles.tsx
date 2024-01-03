import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

export const TimelineIconStyled = styled.img(
  ({ theme }: { theme: Theme }) => `
    max-height: 60%;
    margin: 0 ${theme.spacing[3]}px 0 ${theme.spacing[3]}px;
`,
);

export const DashedLines = styled.div(
  ({ theme, isHidden }: { theme: Theme; isHidden: boolean }) => `
  border-top: ${theme.border.width.thin}px dashed ${theme.colors.surface.text.subdued.lowContrast};
  border-color: ${theme.colors.brand.gray[500].lowContrast};
  opacity: ${isHidden ? 0 : 1};
  margin: 0;`,
);

export const Ellipse1 = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: 90%;    
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background-color: ${theme.colors.brand.primary[400]};
    position: absolute;
    right: 0;
    left: 0;
    bottom: 60%;
    filter: blur(200px);

    @media screen and (min-width: ${theme.breakpoints.l}px) {
      width: 40%; 
      position: absolute;
      right: 70%;
      bottom: 10%;
      filter: blur(200px);
    }
`,
);

export const Ellipse2 = styled.div(
  ({ theme }: { theme: Theme }) => `
    width: 35%;    
    aspect-ratio: 1/1;
    border-radius: ${theme.border.radius.round};
    background-color: ${theme.colors.brand.primary[400]};
    position: absolute;
    left: 70%;
    top: 50%;
    filter: blur(200px);
  `,
);

export const BannerImageStyled = styled.img`
  position: relative;
  bottom: -10px;
  max-height: 400px;
  max-inline-size: 100%;
`;
