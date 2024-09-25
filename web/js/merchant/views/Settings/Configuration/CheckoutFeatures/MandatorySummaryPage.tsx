import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutFeatures } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/index';

import { CHECKOUT_FEATURE_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutFeatures/context/constants';
import { MANDATORT_SUMMARY_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutFeatures/constants/DefaultValue';

const MandatorySummaryPage = () => {
  const { values, handleMandatorySummaryPageToggle } = useCheckoutFeatures();
  return (
    <FeatureToggle
      isChecked={values[CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE]}
      feature={CHECKOUT_FEATURE_FIELDS.MANDATORY_SUMMARY_PAGE}
      title={MANDATORT_SUMMARY_DEFAULT_VALUE.title}
      subTitle={MANDATORT_SUMMARY_DEFAULT_VALUE.subTitle}
      toggleHandler={(isChecked) => handleMandatorySummaryPageToggle(isChecked)}
    />
  );
};

export default MandatorySummaryPage;
