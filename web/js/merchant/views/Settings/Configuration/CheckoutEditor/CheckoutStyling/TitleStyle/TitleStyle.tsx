import React from 'react';

import { TITLE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import RightChildren from './RightChildren';

const TitleStyle = () => {
  return (
    <LineItems
      title={TITLE_DEFAULT_VALUE.title}
      subTitle={TITLE_DEFAULT_VALUE.subTitle}
      rightChildren={<RightChildren />}
    />
  );
};

export default TitleStyle;
