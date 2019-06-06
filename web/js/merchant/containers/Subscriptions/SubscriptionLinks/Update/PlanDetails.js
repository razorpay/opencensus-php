import React from 'react';
import PlanDetails from '../New/PlanDetails';
import Input from 'component/Input';

export default class UpdateSubscriptionLinkPlanDetails extends React.Component {
  render() {
    return (
      <React.Fragment>
        <PlanDetails
          plans={this.props.plans}
          onChangeInPlan={this.props.onChangeInPlan}
          onDateChange={this.props.onDateChange}
          onTimeChange={this.props.onTimeChange}
          fields={this.props.fields}
          internals={this.props.internals}
        />
        <Input.Radio
          label="Apply Changes"
          name="schedule_change_at"
          options={CHANGES_OPTIONS}
          className="Input--vTop"
          defaultValue={this.props.fields.schedule_change_at}
          onChange={this.props.onRadioChange}
        />
      </React.Fragment>
    );
  }
}

const CHANGES_OPTIONS = [
  { label: 'Immediately', value: 'now' },
  { label: 'End of Cycle', value: 'cycle_end' },
];
