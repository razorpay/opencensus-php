import React from 'react';

import {
  StyledEllipse,
  StyledEllipseLeft,
  StyledSquare,
  IntegrationModalIcon,
  StyledSquareBottom,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/LinkAccountInfoContianer';

import { DemoVideoContainerPropTypes } from 'merchant/views/MagicCheckout/ShippingServices/IThinkAccountModal/types';

const DemoVideoContainer = (props: DemoVideoContainerPropTypes): JSX.Element => {
  const { demoVideo: DemoVideo, modalIcon, demoInfoText, demoVideoLink } = props;

  return (
    <>
      <StyledEllipse />
      <StyledEllipseLeft />
      <DemoVideo infoText={demoInfoText} demoVideoLink={demoVideoLink} />
      <StyledSquare />
      <IntegrationModalIcon src={modalIcon} alt="ithink-logistics-icon" />
      <StyledSquareBottom />
    </>
  );
};

export default DemoVideoContainer;
