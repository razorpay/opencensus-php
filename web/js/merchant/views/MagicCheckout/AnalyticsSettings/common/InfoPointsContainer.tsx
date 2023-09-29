import React from 'react';

import {
  InfoContent,
  InfoHighlight,
  InfoPointsWrapper,
  InfoText,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/InfoPointsContainer';

import ListBulletImage from 'merchant/views/MagicCheckout/ShippingServices/assets/list-bullet.svg';

const INFO_POINTS = [
  {
    highlight: 'Benefits of backend integration',
    subText: () =>
      'Get complete view of your website, customer and better tracking by doing backend integration.',
  },
];

const InfoPointsContainer = (): JSX.Element => (
  <InfoPointsWrapper>
    {INFO_POINTS.map((point) => (
      <InfoContent key={point.highlight}>
        <img src={ListBulletImage} alt="bullet" className="benefits-shiprocket-list-icon" />
        <div>
          <InfoHighlight>{point.highlight}</InfoHighlight>
          <InfoText>{point.subText()}</InfoText>
        </div>
      </InfoContent>
    ))}
  </InfoPointsWrapper>
);

export default InfoPointsContainer;
