import React, { useState, useEffect, Suspense } from "react";
import { useLocation, useSearchParams } from "react-router-dom";
const VersioningModal = React.lazy(() => import("./VersioningModal"));
import { shouldOpenVersioningModal } from "./utils";
import { getRekycModalIsOpen, isEligibleForSelfServeRekyc } from "@dashboards/payments/components/SelfServeRekyc/utils";
import { useStore } from '@federated/apps/shell/commonStore';
import { useSplitzService } from "common/splitz";
import { useGetRekycDetails } from "@dashboards/payments/components/SelfServeRekyc/hooks/useGetRekycDetails";

const VersioningBanner: React.FC = () => {
  const [isOpen, setIsOpen] = useState(false);
  const location = useLocation();
  const [searchParams, setSearchParams] = useSearchParams();
  const splitz = useSplitzService();
  const user = useStore((state) => state.session.user);

  const {
    isLoading: isRekycDetailsLoading,
    data: rekycDetails,
  } = useGetRekycDetails({ merchantId: user?.merchant?.id || '', showCurrentData: true });

  const shouldShowSelfServeRekycNotifications = isEligibleForSelfServeRekyc(splitz, user);
  const isReKycModalEnabled = isRekycDetailsLoading ? true : getRekycModalIsOpen(rekycDetails?.status || '', rekycDetails?.deadline || 0);

  const isReKYCModalOpen = shouldShowSelfServeRekycNotifications && isReKycModalEnabled;

  useEffect(() => {
    const { shouldOpen, isQueryParam, runModalOpenSideEffects } = shouldOpenVersioningModal(location.pathname, searchParams);

    if (shouldOpen) {
      // Allow query param-based opening regardless of ReKYC modal state
      if (isQueryParam) {
        setIsOpen(true);
        // Remove the query param from the URL, so that clicking on the CHQ banner opens the modal again, due to route change
        const newSearchParams = new URLSearchParams(searchParams);
        newSearchParams.delete('event');
        setSearchParams(newSearchParams, { replace: true });
      } else if (!isReKYCModalOpen) {
        // For auto-opening, only allow when ReKYC modal is not open
        setIsOpen(true);
        // Call the runModalOpenSideEffects for auto open only if ReKYC modal is not open
        runModalOpenSideEffects?.();
      }
    }
  }, [location.pathname, searchParams, setSearchParams, isReKYCModalOpen]);

  return (
    <>
      {isOpen && (
        <Suspense fallback={<></>}>
          <VersioningModal isOpen={isOpen} closeModal={() => setIsOpen(false)} />
        </Suspense>
      )}
    </>
  );
};

export default VersioningBanner;
