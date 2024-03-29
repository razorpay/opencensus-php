import React from 'react';
import { Text } from '@razorpay/blade/components';

import {
  EditIconContainer,
  EventsPreviewContainer,
  PreviewContent,
  PreviewContentContainer,
  PreviewContentTitle,
  PreviewContentValue,
  PreviewHeader,
  Seperator,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/AnalyticsEventsPreview';
import { AnalyticsEventsPreviewPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

const AnalyticsEventsPreview = (props: AnalyticsEventsPreviewPropsType): JSX.Element => {
  const { analyticsEvents, setShowPreviewMode, eventConfigs } = props;

  return (
    <EventsPreviewContainer>
      <PreviewHeader>
        <Text weight="semibold" color="surface.text.gray.subtle" size="large">
          Events settings
        </Text>
        <EditIconContainer onClick={() => setShowPreviewMode(false)} data-testid="edit-icon">
          <i className="i i-edit_board" />
          Edit
        </EditIconContainer>
      </PreviewHeader>
      <Seperator />
      <PreviewContentContainer>
        {analyticsEvents.map((event) => (
          <PreviewContent key={event.label}>
            <PreviewContentTitle>{event.label}</PreviewContentTitle>
            <PreviewContentValue>
              {eventConfigs?.[event.value] ? 'Enabled' : 'Disabled'}
            </PreviewContentValue>
          </PreviewContent>
        ))}
      </PreviewContentContainer>
    </EventsPreviewContainer>
  );
};

export default AnalyticsEventsPreview;
