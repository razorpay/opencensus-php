import React, { useEffect, useState } from 'react';
import AnnouncementBanner from 'merchant/components/Announcements/AnnouncementBanner';
import { Link, Box } from '@razorpay/blade/components';
import { Banner, Title, Description } from './Styled';
import {
  updateModalConfigDetails,
  fetchModalConfigDetails,
} from 'merchant/reducers/ModalConfigApi';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import {
  BANNER_HEADING,
  TERMINAL_PROCUREMENT_STATUSES,
  STATUSES_FOR_DISPLAYING_BANNER,
} from 'merchant/components/Announcements/TerminalStatus/constant';
import UpiLogo from 'assets/upi-logo.png';

interface TerminalStatusProps {
  isMobile: boolean;
  user: {
    activation_status: string;
  };
}

const TerminalStatus = ({ isMobile, user }: TerminalStatusProps): JSX.Element | null => {
  const [status, setStatus] = useState(TERMINAL_PROCUREMENT_STATUSES.NO_BANNER);

  const handleGotItClick = (): void => {
    analyticsTrack({
      objectName: 'UPI Terminal Status banner',
      actionName: 'Clicked',
      screen: 'home page',
      properties: {
        terminal_procurement_status: status,
        activationStatus: user.activation_status || '',
        ...getCommonAnalyticsProperties(user),
      },
    });
    const newStatus =
      TERMINAL_PROCUREMENT_STATUSES.PENDING === status
        ? TERMINAL_PROCUREMENT_STATUSES.PENDING_ACK
        : TERMINAL_PROCUREMENT_STATUSES.NO_BANNER;

    setStatus(newStatus);
    updateModalConfigDetails({ upi_terminal_procurement_status_banner: newStatus }, 'onboarding');
  };

  const fetchTerminalStatus = (): void => {
    fetchModalConfigDetails('onboarding').then((res) => {
      if (res?.data) {
        const { upi_terminal_procurement_status_banner = TERMINAL_PROCUREMENT_STATUSES.NO_BANNER } =
          res.data || {};

        if (upi_terminal_procurement_status_banner === TERMINAL_PROCUREMENT_STATUSES.PENDING) {
          updateModalConfigDetails(
            { upi_terminal_procurement_status_banner: TERMINAL_PROCUREMENT_STATUSES.PENDING_SEEN },
            'onboarding',
          );
        }

        const updatedValue =
          upi_terminal_procurement_status_banner === TERMINAL_PROCUREMENT_STATUSES.PENDING_SEEN
            ? TERMINAL_PROCUREMENT_STATUSES.PENDING
            : upi_terminal_procurement_status_banner;

        setStatus(updatedValue);

        if (STATUSES_FOR_DISPLAYING_BANNER.includes(updatedValue)) {
          analyticsTrack({
            objectName: 'UPI Terminal Status banner',
            actionName: 'Displayed',
            screen: 'home page',
            properties: {
              terminal_procurement_status: upi_terminal_procurement_status_banner,
              activationStatus: user.activation_status || '',
              ...getCommonAnalyticsProperties(user),
            },
          });
        }
      }
    });
  };

  useEffect(() => {
    fetchTerminalStatus();
  }, []);

  const details = BANNER_HEADING[status];

  if (!STATUSES_FOR_DISPLAYING_BANNER.includes(status)) {
    return null;
  }

  if (isMobile) {
    const { title, message } = details.mobile;
    return (
      <Banner status={status}>
        <div>
          <Title>{title}</Title>
          <Description>{message}</Description>
          <Link onClick={handleGotItClick}>Okay, got it</Link>
        </div>
        <img src={UpiLogo} alt="upi-logo" />
      </Banner>
    );
  }

  const { title, message } = details.desktop;
  return (
    <AnnouncementBanner title={title} canBeClosed={false} theme={details.theme}>
      <Box
        display="flex"
        alignItems="center"
        justifyContent="space-between"
        marginRight="spacing.8"
      >
        <span>{message}</span>
        <div>
          <div className="big-dot-separator" />
          <Link onClick={handleGotItClick}>Okay, got it</Link>
        </div>
      </Box>
    </AnnouncementBanner>
  );
};
export default TerminalStatus;
