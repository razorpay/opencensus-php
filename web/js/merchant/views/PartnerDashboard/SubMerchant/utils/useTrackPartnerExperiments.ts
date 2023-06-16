import { useEffect, useCallback } from 'react';
import { analyticsTrack } from 'common/services/tracking/segment';
import { UserT } from 'merchant/views/PartnerDashboard/TypesDeclare';

export default function useTrackPartnerExperiments(user: UserT): void {
  const {
    isPartnershipForXEnabled,
    isSubMerchantKycResellerEnabled,
    isPartnershipNPS,
    isPartnershipFUX,
    isOnboardAsResellers,
    isShowInvoiceCurrentFY,
    isShowAffordabilityWidget,
    isShowAffWidgetShopifyWaitlist,
    isShowAffWidgetWoocWaitlist,
    isShowSegregatedCreditEmi,
    isShowResumeOnboarding,
    isEnablePurePlatformSwitch,
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
        isPartnershipNPS,
        isPartnershipFUX,
        isOnboardAsResellers,
        isShowInvoiceCurrentFY,
        isShowAffordabilityWidget,
        isShowAffWidgetShopifyWaitlist,
        isShowAffWidgetWoocWaitlist,
        isShowSegregatedCreditEmi,
        isShowResumeOnboarding,
        isEnablePurePlatformSwitch,
      },
    });
  }, [
    isPartnershipForXEnabled,
    isSubMerchantKycResellerEnabled,
    isPartnershipNPS,
    isPartnershipFUX,
    user,
    isOnboardAsResellers,
    isShowInvoiceCurrentFY,
    isShowAffordabilityWidget,
    isShowAffWidgetShopifyWaitlist,
    isShowAffWidgetWoocWaitlist,
    isShowSegregatedCreditEmi,
    isShowResumeOnboarding,
    isEnablePurePlatformSwitch,
  ]);

  useEffect(() => {
    trackExperiments();
  }, [trackExperiments]);
}
