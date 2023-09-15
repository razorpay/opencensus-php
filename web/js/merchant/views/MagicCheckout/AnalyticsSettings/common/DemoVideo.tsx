import React from 'react';

import { DemoVideoPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  StyledVideo,
  VideoInfoText,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/DemoVideo';

const DemoVideo = ({ infoText, demoVideoLink }: DemoVideoPropsType): JSX.Element => (
  <div>
    <StyledVideo width="100%" controls autoPlay loop>
      <source src={demoVideoLink} type="video/mp4" />
    </StyledVideo>
    <VideoInfoText>
      <i className="i i-info-circle" />
      <p>{infoText}</p>
    </VideoInfoText>
  </div>
);

export default DemoVideo;
