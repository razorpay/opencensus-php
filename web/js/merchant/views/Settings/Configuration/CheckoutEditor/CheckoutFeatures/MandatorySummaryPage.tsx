import React from 'react';

import FeatureToggle from 'merchant/views/Settings/Configuration/components/Configuration/FeatureToggle';

import { useCheckoutEditor } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/index';

import { MANDATORT_SUMMARY_DEFAULT_VALUE } from 'merchant/views/Settings/Configuration/CheckoutEditor/CheckoutFeatures/constants/DefaultValue';
import { CHECKOUT_EDITOR_FIELDS } from 'merchant/views/Settings/Configuration/CheckoutEditor/context/constants';

const MandatorySummaryPage = ({ blockData }: any) => {
  const { values, handleMandatorySummaryPageToggle } = useCheckoutEditor();

  return (
    <FeatureToggle
      isFeature
      isChecked={values[CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE]}
      feature={CHECKOUT_EDITOR_FIELDS.MANDATORY_SUMMARY_PAGE}
      title={MANDATORT_SUMMARY_DEFAULT_VALUE.title}
      subTitle={MANDATORT_SUMMARY_DEFAULT_VALUE.subTitle}
      blockData={blockData}
      toggleHandler={(isChecked) => handleMandatorySummaryPageToggle(isChecked)}
      subSectionName="mandate-summary-page"
    />
  );
};

export default MandatorySummaryPage;
