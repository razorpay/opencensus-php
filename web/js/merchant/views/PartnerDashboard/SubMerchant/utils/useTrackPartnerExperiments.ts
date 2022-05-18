import { useEffect, useCallback } from 'react';
import { analyticsTrack } from 'common/services/tracking/segment';
import { UserT } from 'merchant/views/PartnerDashboard/TypesDeclare';

export default function useTrackPartnerExperiments(user: UserT): void {
  const {
    isPartnershipForXEnabled,
    isSubMerchantKycResellerEnabled,
    isMerchantValidation,
    isPartnershipNPS,
    isPartnershipFUX,
  } = user;

  const trackExperiments = useCallback(() => {
    analyticsTrack({
      objectName: 'partner experiments',
      actionName: 'load',
      screen: 'partnership',
      eventAction: 'initiated',
      activationType: 'partner-experiments',
      user,
      properties: {
        isPartnershipForXEnabled,
        isSubMerchantKycResellerEnabled,
        isMerchantValidation,
        isPartnershipNPS,
        isPartnershipFUX,
      },
    });
  }, [
    isPartnershipForXEnabled,
    isSubMerchantKycResellerEnabled,
    isMerchantValidation,
    isPartnershipNPS,
    isPartnershipFUX,
    user,
  ]);

  useEffect(() => {
    trackExperiments();
  }, [trackExperiments]);
}
