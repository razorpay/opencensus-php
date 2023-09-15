import React from 'react';

import { LinkAccountInfoContainerPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  StyledEllipse,
  StyledEllipseLeft,
  StyledSquare,
  IntegrationModalIcon,
  StyledSquareBottom,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/LinkAccountInfoContianer';

const LinkAccountInfoContainer = (props: LinkAccountInfoContainerPropsType): JSX.Element => {
  const { demoVideo: DemoVideo, modalIcon, demoInfoText, demoVideoLink } = props;

  return (
    <>
      <StyledEllipse />
      <StyledEllipseLeft />
      <DemoVideo infoText={demoInfoText} demoVideoLink={demoVideoLink} />
      <StyledSquare />
      <IntegrationModalIcon src={modalIcon} alt="google-analytics-icon" />
      <StyledSquareBottom />
    </>
  );
};

export default LinkAccountInfoContainer;
