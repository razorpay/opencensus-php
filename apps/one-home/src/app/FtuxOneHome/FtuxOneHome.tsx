import React, { useEffect, useState } from 'react';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  Box,
  Modal,
  ModalBody,
  ModalFooter,
} from '@razorpay/blade/components';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import { getItem, setItem } from 'common/utils/localStorage';
import { FtuxConsentBody, FtuxConsentFooter } from './components/FtuxConsent';
import { FtuxConsentOptOutBody, FtuxConsentOptOutFooter } from './components/FtuxOptOutConsent';
import { FtuxHandlers } from './types';
import { analyticsTrack, getCommonAnalyticsProperties } from '@libs/shared-utils';

// Configuration for different modal states
const consentConfig = {
  consent: {
    body: <FtuxConsentBody />,
    footer: (handlers: FtuxHandlers) => (
      <FtuxConsentFooter
        onOptOut={handlers.handleOptOutClick}
        onProceed={handlers.handleProceedClick}
      />
    ),
  },
  optOut: {
    body: <FtuxConsentOptOutBody />,
    footer: (handlers: FtuxHandlers) => (
      <FtuxConsentOptOutFooter onGoBack={handlers.handleGoBackClick} />
    ),
  },
};

const FtuxOneHome = (): React.ReactElement | null => {
  const { theme } = useTheme();
  const { matchedDeviceType } = useBreakpoint({ breakpoints: theme.breakpoints });
  const isMobile = matchedDeviceType === 'mobile';

  const [modalState, setModalState] = useState<'consent' | 'optOut' | null>(null);

  useEffect(() => {
    const isOneHomeConsentAccepted = getItem('isOneHomeConsentAccepted');
    if (!isOneHomeConsentAccepted || isOneHomeConsentAccepted === 'false') {
      setItem('isOneHomeConsentAccepted', 'false');
      setModalState('consent');
    }
  }, []);

  const handleOptOutClick = () => {
    setModalState('optOut');
    setItem('hasUserInteractedWithHomeConsent', 'true');
  };
  const handleProceedClick = () => {
    analyticsTrack({
      objectName: 'one home consent proceed',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties((window as any).rzp_user),
      },
    });

    setItem('isOneHomeConsentAccepted', 'true');
    setItem('hasUserInteractedWithHomeConsent', 'true');
    setModalState(null);
  };
  const handleGoBackClick = () => setModalState('consent');

  // Handlers object to be passed to components dynamically
  const handlers = { handleOptOutClick, handleProceedClick, handleGoBackClick };

  // If no active modal, return null
  if (!modalState) return null;

  return isMobile ? (
    <BottomSheet isOpen={!!modalState}>
      <BottomSheetBody>{consentConfig[modalState].body}</BottomSheetBody>
      <BottomSheetFooter>{consentConfig[modalState].footer(handlers)}</BottomSheetFooter>
    </BottomSheet>
  ) : (
    <Modal isOpen={!!modalState} onDismiss={() => {}} size="small">
      <ModalBody>{consentConfig[modalState].body}</ModalBody>
      <ModalFooter>{consentConfig[modalState].footer(handlers)}</ModalFooter>
    </Modal>
  );
};

export default FtuxOneHome;
