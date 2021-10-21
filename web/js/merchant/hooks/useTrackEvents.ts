import { useCallback } from 'react';

import { analyticsTrack } from 'common/services/tracking/segment';
import { getCommonSegmentProperties } from 'common/utils/rzp-utils';
import { useApp } from 'common/context/App';
import useActivation from 'merchant/views/onboarding/mobile/hooks/useActivation';

function useTrackEvents(): (trackingData) => void {
  const { user } = useApp();
  const { data } = useActivation();

  const trackEvents = useCallback(
    (trackingData) => {
      analyticsTrack({
        ...trackingData,
        properties: {
          ...trackingData.propperties,
          business_type: data?.business_type,
          activation_status: data?.activation_status,
          user_business_category: data?.business_category,
          user_business_sub_category: data?.business_subcategory,
          ...getCommonSegmentProperties(user),
        },
        user,
      });
    },
    [data],
  );

  return trackEvents;
}

export default useTrackEvents;
