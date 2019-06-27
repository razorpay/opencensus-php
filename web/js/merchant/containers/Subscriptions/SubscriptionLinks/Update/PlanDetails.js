import React from 'react';
import PlanDetails from '../common/PlanDetails';
import Input from 'component/Input';

export default class UpdateSubscriptionLinkPlanDetails extends React.Component {
  render() {
    const props = this.props,
      showScheduleChange =
        props.status !== 'created' && props.status !== 'authenticated';

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
        />

        <Input.Check
          label="Notify Customer"
          name="customer_notify"
          className="Input--vTop"
          checked={props.fields.customer_notify}
          fieldLabel="Notify customer for this update and future changes."
        />

        {showScheduleChange && (
          <Input.Radio
            label="Apply Changes"
            className="Input--vTop"
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

const CHANGES_OPTIONS = [
  { label: 'Immediately', value: 'now' },
  { label: 'End of Cycle', value: 'cycle_end' },
];
