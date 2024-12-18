import React from 'react';

import RightChildren from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/LanguageSettings/RightChildren';
import LineItems from 'merchant/views/Settings/Configuration/components/Configuration/LineItems';

import { LANGUAGE_SETTINGS_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';

const LanguageSettings = ({ blockData }: any) => {
  return (
    <LineItems
      title={LANGUAGE_SETTINGS_DEFAULT_VALUE.title}
      subTitle={LANGUAGE_SETTINGS_DEFAULT_VALUE.subTitle}
      blockData={blockData}
      rightChildren={<RightChildren />}
    />
  );
};

export default LanguageSettings;
