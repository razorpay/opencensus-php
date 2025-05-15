import React, { useEffect, useState } from 'react';
import {
  BottomSheet,
  BottomSheetBody,
  BottomSheetFooter,
  BottomSheetHeader,
  Box,
  Button,
  Modal,
  ModalBody,
  ModalFooter,
} from '@razorpay/blade/components';
import { useBreakpoint, useTheme } from '@razorpay/blade/utils';
import { getItem, setItem } from 'common/utils/localStorage';
import { FtuxConsentBody, FtuxConsentFooter } from './components/FtuxConsent';
import { FtuxConsentOptOutBody, FtuxConsentOptOutFooter } from './components/FtuxOptOutConsent';
import { FtuxHandlers } from './types';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';

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
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

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
    trackOneHomeAnalytics({
      objectName: 'one home consent proceed',
      actionName: 'clicked',
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

  //TODO: Bottomsheet is not working on mobile if default state is open, removing it for now
  //Slack: https://razorpay.slack.com/archives/C06F9MYVBR7/p1747224046006079
  return (
    <Modal isOpen={!!modalState} onDismiss={() => {}} size="small">
      <ModalBody>{consentConfig[modalState]?.body}</ModalBody>
      <ModalFooter>{consentConfig[modalState]?.footer(handlers)}</ModalFooter>
    </Modal>
  );
};

export default FtuxOneHome;
