import React from 'react';
import PlanDetails from '../components/PlanDetails';
import Input from 'common/new-ui/Input';

const CHANGES_OPTIONS = [
  { label: 'Immediately', value: 'now' },
  { label: 'End of Cycle', value: 'cycle_end' },
];

export default class UpdateSubscriptionLinkPlanDetails extends React.Component {
  render() {
    const { props } = this;
    const showScheduleChange = props.status !== 'created' && props.status !== 'authenticated';

    return (
      <React.Fragment>
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
        />

        <Input.Check
          class="Input--vTop"
          label="Notify Customer"
          name="customer_notify"
          checked={props.fields.customer_notify}
          fieldLabel="Notify customer for this update and future charges."
        />

        {showScheduleChange && (
          <Input.Radio
            label="Apply Changes"
            class="Input--vTop"
            options={CHANGES_OPTIONS}
            name="schedule_change_at"
            onChange={props.onRadioChange}
            defaultValue={props.fields.schedule_change_at}
          />
        )}
      </React.Fragment>
    );
  }
}
