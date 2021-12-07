const getFeature = (features, feature) => {
  return features.find((obj) => obj.feature === feature) || {};
};

export { getFeature };
