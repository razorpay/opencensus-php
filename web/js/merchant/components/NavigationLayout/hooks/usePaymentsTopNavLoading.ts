import { useEffect, useState } from 'react';
import { useStore } from '@federated/apps/shell/commonStore';

export function usePaymentsTopNavLoading() {
  const session = useStore((state) => state.session);
  const app = useStore((state) => state.app);
  const trustedBadge = useStore((state) => state.trustedBadge);
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    if (!session || !session.user || !app) {
      setIsLoading(true);
      return;
    }

    const { isOrgRZP, isCountryIndia, isOrgAllowedFunctionality } = session.user;
    const { partnerMode } = session;
    const { isWebView, windowWidth } = app;

    if (
      isOrgRZP !== undefined &&
      isCountryIndia !== undefined &&
      partnerMode !== undefined &&
      isWebView !== undefined &&
      windowWidth !== undefined &&
      trustedBadge !== undefined &&
      isOrgAllowedFunctionality !== undefined
    ) {
      setIsLoading(false);
    } else {
      setIsLoading(true);
    }
  }, [session, app, trustedBadge]);

  return { isTopNavActionsLoading: isLoading };
}
