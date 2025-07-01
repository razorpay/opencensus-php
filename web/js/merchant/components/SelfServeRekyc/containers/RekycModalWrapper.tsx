import React, {useEffect, useState} from 'react'
import { useMobile } from 'common/hooks/useMobile';
import { moment } from '../moment';

import RekycModal from 'merchant/components/SelfServeRekyc/components/RekycModal';

import { RekycModalWrapperProps } from 'merchant/components/SelfServeRekyc/types';

import { openTicketModal, openUrlInNewTab, getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { analyticsTrack } from '@libs/shared-utils';

import { SELF_SERVE_REKYC_HIDE_MODAL } from 'merchant/components/SelfServeRekyc/constants';

const RekycModalWrapper = (props: RekycModalWrapperProps) => {
  const {
    modalInfo,
    daysFromDeadline,
    deadlineDate,
    rekycUrl,
    rekycStatus,
  } = props;

  const isMobile = useMobile();
  const [isOpen, setIsOpen] = useState(true);

  const onDismiss = () => {
    analyticsTrack({
      objectName: 'self serve rekyc modal',
      actionName: 'closed',
      screen: 'self serve rekyc modal',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
      }
    });

    const hideModalConfig = {
      dismissedOn: moment().unix()
    }

    const isModalConfigAvailable = localStorage.getItem(SELF_SERVE_REKYC_HIDE_MODAL);
    if (!isModalConfigAvailable) {
      localStorage.setItem(SELF_SERVE_REKYC_HIDE_MODAL, JSON.stringify(hideModalConfig));
    }

    setIsOpen(false);
  };

  const handleUpdateKycClick = (rekycUrl: string) => {
    analyticsTrack({
      objectName: 'self serve rekyc modal cta',
      actionName: 'clicked',
      screen: 'self serve rekyc modal',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
        redirectionUrl: rekycUrl,
        ctaText: modalInfo?.ctaText || '',
      }
    });

    if (rekycUrl !== 'contactSupport') {
      openUrlInNewTab(rekycUrl);
    } else {
      openTicketModal({
        subject: 'ReKYC',
        description: 'Please help me with the rekyc process.',
        tags: ['ReKYC'],
      });
    }
  }

  useEffect(() => {
    analyticsTrack({
      objectName: 'self serve rekyc modal',
      actionName: 'displayed',
      screen: 'self serve rekyc modal',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user, { addUserProperties: true }),
        rekycStatus: rekycStatus ?? '',
        experimentName: 'self-serve-rekyc',
      }
    });
  }, [rekycStatus]);

  return (
      modalInfo ?
      <RekycModal
        isOpen={isOpen}
        isMobile={isMobile}
        onDismiss={onDismiss}
        onCtaClick={handleUpdateKycClick}
        modalInfo={modalInfo}
        daysFromDeadline={daysFromDeadline}
        deadlineDate={deadlineDate}
        rekycUrl={rekycUrl}
      /> : null
  )
}

export default RekycModalWrapper;