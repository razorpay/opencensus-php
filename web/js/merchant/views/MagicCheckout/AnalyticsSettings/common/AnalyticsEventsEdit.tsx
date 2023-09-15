import React from 'react';
import { connect } from 'react-redux';

import { Button } from '@razorpay/blade/components';
import SettingsToggle from 'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle';
import SettingsLabel from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/SettingsLabel';

import { AnalyticsEventsEditPropsType } from 'merchant/views/MagicCheckout/AnalyticsSettings/types';

import {
  EventsToggleContainer,
  ToggleWrapper,
  SaveEventsCtaContainer,
} from 'merchant/views/MagicCheckout/AnalyticsSettings/styledComponents/AnalyticsEventsEdit';

const AnalyticsEventsEdit = (props: AnalyticsEventsEditPropsType): JSX.Element => {
  const {
    analyticsEvents,
    eventConfigs,
    updateEventConfigs,
    saveEventConfigs,
    isSaveEventsCtaDisabled,
    isLoading,
  } = props;

  return (
    <>
      <EventsToggleContainer>
        {analyticsEvents.map((event) => {
          return (
            <ToggleWrapper key={event.label}>
              <SettingsLabel value={event.label} popoverContent={event.infoText} />
              <SettingsToggle
                setting={{ value: eventConfigs?.[event.value] || '' }}
                onToggle={(checked: boolean) => updateEventConfigs(checked, event.value)}
              />
            </ToggleWrapper>
          );
        })}
      </EventsToggleContainer>
      <SaveEventsCtaContainer>
        <Button
          type="button"
          size="medium"
          onClick={saveEventConfigs}
          isDisabled={isSaveEventsCtaDisabled}
          isLoading={isLoading}
        >
          Save events
        </Button>
      </SaveEventsCtaContainer>
    </>
  );
};

const mapStateToProps = (state) => {
  return {
    isLoading: state.magicAnalyticsSettings.isLoading.saveEvents,
  };
};

export default connect(mapStateToProps, null)(AnalyticsEventsEdit);
