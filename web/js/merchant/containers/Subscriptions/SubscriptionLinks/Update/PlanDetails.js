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
          options={['Immediately', 'End of Cycle']}
          className="Input--vTop"
          onChange={this.props.handleApplyChanges}
        />
      </React.Fragment>
    );
  }
}
