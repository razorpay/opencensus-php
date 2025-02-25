import { analyticsTrack } from '@libs/web-nexus/common/services/tracking/segment';

export const trackWithSegment = (props) => {
  const enrichedProps = {
    ...props,
    screen: props?.screen || 'Partner Signup Screen',
    properties: {
      ...props?.properties,
      userId: 'UNKNWON_USER',
      partnerSignupFlag: true,
      pageUrl: window?.location?.pathname,
    },
  };
  return analyticsTrack(enrichedProps);
};
