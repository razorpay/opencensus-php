import React from 'react';

import { Heading } from '@razorpay/blade/components';

import { AnalyticsEventsPreviewPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  EventsPreviewContainer,
  PreviewHeader,
  EditIconContainer,
  PreviewContentContainer,
  Seperator,
  PreviewContent,
  PreviewContentTitle,
  PreviewContentValue,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/AnalyticsEventsPreview';

const AnalyticsEventsPreview = (props: AnalyticsEventsPreviewPropsType): JSX.Element => {
  const { analyticsEvents, setShowPreviewMode, eventConfigs } = props;

  return (
    <EventsPreviewContainer>
      <PreviewHeader>
        <Heading size="small" weight="bold" color="surface.text.subtle.lowContrast">
          Events settings
        </Heading>
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
