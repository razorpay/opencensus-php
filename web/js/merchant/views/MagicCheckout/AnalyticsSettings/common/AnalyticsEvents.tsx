import React from 'react';
import styled from 'styled-components';

import { Heading } from '@razorpay/blade/components';
import AnalyticsEventsPreview from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEventsPreview';
import AnalyticsEventsEdit from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEventsEdit';

import { AnalyticsEventsPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

const EventsContainer = styled.div`
  margin-top: 12px;
  padding: 24px;
  background-color: #fff;
`;

const AnalyticsEvents = (props: AnalyticsEventsPropsType): JSX.Element => {
  const {
    analyticsEvents,
    header,
    eventConfigs,
    updateEventConfigs,
    // eslint-disable-next-line @typescript-eslint/naming-convention
    showPreviewMode,
    setShowPreviewMode,
    saveEventConfigs,
    isSaveEventsCtaDisabled,
  } = props;

  return (
    <EventsContainer>
      <Heading
        size="small"
        weight="bold"
        color="surface.text.subtle.lowContrast"
      >{`${header} events to trigger`}</Heading>
      {!showPreviewMode ? (
        <AnalyticsEventsEdit
          analyticsEvents={analyticsEvents}
          eventConfigs={eventConfigs}
          updateEventConfigs={updateEventConfigs}
          saveEventConfigs={saveEventConfigs}
          isSaveEventsCtaDisabled={isSaveEventsCtaDisabled}
        />
      ) : (
        <AnalyticsEventsPreview
          analyticsEvents={analyticsEvents}
          setShowPreviewMode={setShowPreviewMode}
          eventConfigs={eventConfigs}
        />
      )}
    </EventsContainer>
  );
};

export default AnalyticsEvents;
