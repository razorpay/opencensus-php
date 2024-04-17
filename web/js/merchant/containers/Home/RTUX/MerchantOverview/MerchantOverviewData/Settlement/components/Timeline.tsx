import React from 'react';
import { Skeleton, Theme } from '@razorpay/blade/components';
import styled from 'styled-components';

import DoneIcon from 'assets/rtux/timelineitem-done.svg';
import InProgressIcon from 'assets/rtux/timelineitem-in-progress.svg';
import Image from 'common/ui/Image';
import { TimelineItemIconKeys } from 'merchant/containers/Home/RTUX/MerchantOverview/types';

const TimelineItemIcon = styled.div`
  position: absolute;
  left: -0.55rem;
  img {
    vertical-align: unset;
  }
`;

const TimelineItemContainer = styled.li`
  margin-left: 1rem;
`;

export const Timeline = styled.ul(
  ({ theme, isMobile }: { theme: Theme; isMobile?: boolean }) => `
    list-style: none;
    padding-left: 0;
    margin: 0;
    position: relative;
    border-left: ${theme.border.width.thicker}px solid ${theme.colors.surface.border.gray.muted};
    border-right: 0;
    margin-left: 6px;
    margin-bottom: ${theme.spacing[4]}px;

    & > *:not(:last-child) {
      margin-bottom:  ${theme.spacing[isMobile ? 6 : 0]}px;
    }
  `,
);

export const TimelineIcons = {
  [TimelineItemIconKeys.in_progress]: (): JSX.Element => <Image src={InProgressIcon} />,
  [TimelineItemIconKeys.done]: (): JSX.Element => <Image src={DoneIcon} />,
  [TimelineItemIconKeys.loading]: (): JSX.Element => <Skeleton width="16px" height="16px" />,
};

export const TimelineItem: React.FC<{ icon: TimelineItemIconKeys; children: React.ReactChild }> = ({
  icon,
  children,
}) => {
  const Icon = TimelineIcons[icon];
  return (
    <TimelineItemContainer>
      {Icon ? (
        <TimelineItemIcon>
          <Icon />
        </TimelineItemIcon>
      ) : null}
      {children}
    </TimelineItemContainer>
  );
};
