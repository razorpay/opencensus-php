import React from 'react';
import PlanDetails from '../New/PlanDetails';
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
          status={props.status}
          plans={props.plans}
          fields={props.fields}
          internals={props.internals}
          onDateChange={props.onDateChange}
          onTimeChange={props.onTimeChange}
          onChangeInPlan={props.onChangeInPlan}
        />
        {showScheduleChange && (
          <Input.Radio
            label="Apply Changes"
            options={CHANGES_OPTIONS}
            name="schedule_change_at"
            className="Input--vTop"
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
