import { findBy } from 'common/utils/rzp-utils';
import { affordabilityFeaturesMapping } from './data';

// Get all affordability feature flags
const getAffFeatureFlag = (flag) => {
  return findBy(window.rzp_user.aff_features, 'feature', flag);
};

const generatePayload = () => {
  let featurePayload = {};
  const oldFeatureFlag = getAffFeatureFlag(affordabilityFeaturesMapping.AFFORDABILITY_WIDGET);
  const newFeatureFlag = getAffFeatureFlag(affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET);

  // if both feature flag are disabled send both as true
  if (!oldFeatureFlag.value && !newFeatureFlag.value) {
    featurePayload = {
      [affordabilityFeaturesMapping.AFFORDABILITY_WIDGET]: true,
      [affordabilityFeaturesMapping.AFFORDABILITY_WIDGET_SET]: true,
    };
  } else {
    // If any of the one flag is disabled get the one that is disabled
    // and only enable that flag
    const disabledFlag = findBy(window.rzp_user.aff_features, 'value', false);
    featurePayload = {
      [disabledFlag.feature]: true,
    };
  }

  const data = {
    features: {
      ...featurePayload,
    },
    should_sync: 0,
  };

  return data;
};

const filterByArray = (array, prop, valueArray) => {
  return array.filter((item) => {
    return valueArray.includes(item[prop]);
  });
};

export { getAffFeatureFlag, generatePayload, filterByArray };
