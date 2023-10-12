import { useEffect, useCallback } from 'react';
import { analyticsTrack } from 'common/services/tracking/segment';
import { UserT } from 'merchant/views/PartnerDashboard/TypesDeclare';

export default function useTrackPartnerExperiments(user: UserT): void {
  const {
    isSubMerchantKycEnabled,
    isPartnershipNPS,
    isPartnershipFUX,
    isOnboardAsResellers,
    isShowInvoiceCurrentFY,
    isShowAffordabilityWidget,
    isShowAffWidgetShopifyWaitlist,
    isShowAffWidgetWoocWaitlist,
    isShowSegregatedCreditEmi,
    isShowResumeOnboarding,
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
        isSubMerchantKycEnabled,
        isPartnershipNPS,
        isPartnershipFUX,
        isOnboardAsResellers,
        isShowInvoiceCurrentFY,
        isShowAffordabilityWidget,
        isShowAffWidgetShopifyWaitlist,
        isShowAffWidgetWoocWaitlist,
        isShowSegregatedCreditEmi,
        isShowResumeOnboarding,
      },
    });
  }, [
    isSubMerchantKycEnabled,
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
  ]);

  useEffect(() => {
    trackExperiments();
  }, [trackExperiments]);
}
