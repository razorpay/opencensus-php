import React from 'react';
import { Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

const StyledBackgroundIntroVideo = styled.video(
  ({ theme }: { theme: Theme }) => `
  position: absolute;
  z-index: -1;
  inset: ${theme.spacing[0]};
  width: 100%;
  height: 100%;
  object-fit: cover;

  /**
   * This background-color is the dominant color of the video.
   * It is used as a fallback when the video has not been loaded yet.
   */
  background-color: #a53277;
`,
);

const PROD_VIDEO_URL =
  'https://cdn.razorpay.com/static/assets/rize/rzp-dashboard/marketplace/landing-gradient-bg.webm';
const NON_PROD_VIDEO_URL =
  'https://betacdn.np.razorpay.in/static/assets/rize/rzp-dashboard/marketplace/landing-gradient-bg.webm';

const BackgroundIntroVideo = (): JSX.Element => (
  <StyledBackgroundIntroVideo
    src={process.env.PUBLIC_ENV === 'development' ? NON_PROD_VIDEO_URL : PROD_VIDEO_URL}
    autoPlay
    loop
    muted
    playsInline
  />
);

export default BackgroundIntroVideo;
