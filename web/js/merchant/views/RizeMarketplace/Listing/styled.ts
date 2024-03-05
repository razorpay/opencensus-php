import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import YoutubeVideo from './components/YoutubeVideo';

export const ProductLogo = styled.img(
  ({ theme }: { theme: Theme }) => `
  border-radius: ${theme.border.radius.large}px;
  background-color: ${theme.colors.static.white};
  width: 64px;
  height: 64px;
  object-fit: cover;
  flex-shrink: 0;
`,
);

export const StyledYoutubeVideo = styled(YoutubeVideo)(
  ({ theme }: { theme: Theme }) => `
  width: 100%;
  margin-bottom: -6.25rem;
  box-shadow: ${theme.elevation.lowRaised};

  @media (min-width: ${theme.breakpoints.m}px) {
    width: auto;
    height: 250px;
  }

  @media (min-width: ${theme.breakpoints.l}px) {
    margin-bottom: ${theme.spacing[0]}px;
  }
`,
);
