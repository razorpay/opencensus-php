/*
  These are features that are unique to india and malaysia, in this list we only added the base features to warn, not all of the features to ensure not to overwhelm the developers. Based on the requirements, we can slowly improve this list.

  This list contains
    - neft, rtgs, imps, upi, rbi, pan etc.. for  common occurrence in code based check and banners
    - netbanking, this only getting used in india but not in malaysia. For malaysia you have to handle the checks based on fpx.
    - GST added, as malaysia has the SST only
*/
const FEATURES_LIST = [
  'pan',
  'upi',
  'neft',
  'rtgs',
  'imps',
  'nach',
  'rbi',
  'gst',
  'sebi',
  'netbanking',
];

const isRegionCentricFeaturesFound = (_value) => {
  const value = _value.toLowerCase().split(' ');
  if (FEATURES_LIST.some((feature) => value.includes(feature))) {
    return `Avoid using the region specific features directly. Please ensure these this feature not enabled for the regions that are not intended to. If it is already handled, then please ignore.`;
  }

  return false;
};

module.exports = isRegionCentricFeaturesFound;
