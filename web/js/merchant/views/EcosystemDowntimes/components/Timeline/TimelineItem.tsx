import React from 'react';
import { CircleIcon } from '@razorpay/blade/components';
import {
  TimelineItemConnector,
  TimelineItemConnectorContainer,
  TimelineItemContainer,
  TimelineItemContentContainer,
} from './styles';

type TimelineItemTypes = {
  icon?: JSX.Element;
  content?: JSX.Element | string;
  isConnectorRequired: boolean;
};

const TimelineItem = ({ icon, content, isConnectorRequired }: TimelineItemTypes): JSX.Element => {
  return (
    <TimelineItemContainer aria-label="timeline-item">
      <TimelineItemConnectorContainer>
        <div className="icon-container" aria-label="timeline-icon">
          {icon || <CircleIcon color="feedback.icon.neutral.intense" size="medium" />}
        </div>
        {isConnectorRequired ? <TimelineItemConnector aria-label="timeline-connector" /> : ''}
      </TimelineItemConnectorContainer>
      <TimelineItemContentContainer aria-label="timeline-content">
        {content}
      </TimelineItemContentContainer>
    </TimelineItemContainer>
  );
};

export default TimelineItem;
