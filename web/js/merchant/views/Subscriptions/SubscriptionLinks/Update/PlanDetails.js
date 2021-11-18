import React from 'react';
import PlanDetails from '../components/PlanDetails';
import Input from 'common/new-ui/Input';
import analytics from '../../analytics';

const CHANGES_OPTIONS = [
  { label: 'Immediately', value: 'now', eventLabel: 'immediate' },
  { label: 'End of Cycle', value: 'cycle_end', eventLabel: 'end_of_cycle' },
];

export default class UpdateSubscriptionLinkPlanDetails extends React.Component {
  render() {
    const { props } = this;
    const showScheduleChange = props.status !== 'created' && props.status !== 'authenticated';

    return (
      <>
        <PlanDetails
          isEdit
          plans={props.plans}
          fields={props.fields}
          status={props.status}
          internals={props.internals}
          onDateChange={props.onDateChange}
          onTimeChange={props.onTimeChange}
          onChangeInPlan={props.onChangeInPlan}
          offers={props.offers}
          showOffers={props.showOffers}
          onChangeInOffer={props.onChangeInOffer}
          cloneOptions={props.cloneOptions}
        />

        <Input.Check
          class="Input--vTop"
          label="Notify Customer"
          name="customer_notify"
          checked={props.fields.customer_notify}
          fieldLabel="Notify customer for this update and future charges."
          onBlur={() => {
            analytics.track('subscription.update.notify', props.cloneOptions);
          }}
        />

        {showScheduleChange && (
          <Input.Radio
            label="Apply Changes"
            class="Input--vTop"
            options={CHANGES_OPTIONS}
            name="schedule_change_at"
            onChange={(e) => {
              const selectedOption = CHANGES_OPTIONS.find((opt) => opt.value === e.target.value);
              analytics.track(
                `subscription.update.${selectedOption.eventLabel}`,
                props.cloneOptions,
              );
              props.onRadioChange(e);
            }}
            defaultValue={props.fields.schedule_change_at}
          />
        )}
      </>
    );
  }
}
