import React from 'react';

import RightChildren from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/FontStyle/RightChildren';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import { FONT_STYLE_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutStyling/constants/DefaultValue';

const FontStyle = () => {
  return (
    <LineItems
      title={FONT_STYLE_DEFAULT_VALUE.title}
      subTitle={FONT_STYLE_DEFAULT_VALUE.subTitle}
      rightChildren={<RightChildren />}
    />
  );
};

export default FontStyle;
