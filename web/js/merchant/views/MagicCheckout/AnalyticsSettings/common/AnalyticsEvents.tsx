import React from 'react';
import { Text } from '@razorpay/blade/components';
import styled from 'styled-components';

import AnalyticsEventsEdit from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEventsEdit';
import AnalyticsEventsPreview from 'merchant/views/MagicCheckout/AnalyticsSettings/common/AnalyticsEventsPreview';
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
      <Text
        weight="semibold"
        color="surface.text.gray.subtle"
        size="large"
      >{`${header} events to trigger`}</Text>
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
