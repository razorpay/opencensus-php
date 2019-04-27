import { TypeAhead } from 'react-power-select';

import Amount from 'rzp/ui/Amount';

import Input, { Label, Description } from 'component/Input';

import { getIntervalCycle } from 'rzp/utils/rzp-utils';

import QuantitySelector from './QuantitySelector';

export default class NewSubscriptionLinkPlanDetails extends React.Component {
  constructor({ plans, fields }) {
    super();
    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields) || {};
  }

  componentWillReceiveProps({ plans, fields }) {
    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields) || {};
  }

  validateTotalCount = val => {
    if (!val) return 'Total Count is mandatory';

    if (val > planPeriodToMaxCycleMap[(this.selectedPlan || {}).period]) {
      return 'Billing cycles cannot exceed the period of 10 years';
    }
  };

  render() {
    const { fields, internals, ...props } = this.props;

    const plans = this.plans;

    const selectedPlan = this.selectedPlan;

    const dateInMoment = !!fields.start_at
      ? moment(fields.start_at, 'X')
      : undefined;

    return (
      <>
        <div class="Input Input--required">
          <Label text="Select Plan" />
          <div className="Input-content">
            <div className="Input-elWrapper">
              <TypeAhead
                options={plans}
                disabled={props.plans.loading}
                class="ps-in-modal"
                searchIndices={['id', 'name', 'description']}
                placeholder={
                  props.plans.loading ? 'Loading...' : 'Select a plan'
                }
                optionComponent={PlanOption}
                selectedOptionLabelPath="name"
                onChange={props.onChangeInPlan}
                selected={selectedPlan}
              />
            </div>
            {fields.plan_id && (
              <QuantitySelector
                rate={selectedPlan.amount}
                quantity={fields.quantity}
                informativeMessage={getInformativeMessage(selectedPlan)}
              />
            )}
          </div>
        </div>

        <Input.Check
          label="Start Date"
          fieldLabel="Immediate, subscriptions starts with the first payment"
          class="Input--vTop"
          data-name="_startsImmediately"
          checked={internals._startsImmediately}
          required
        />

        <Input.Group class="InputGroup--inline InputGroup--near">
          <div class="Input-content">
            <Input.ToCalendar
              name="start_at"
              placeholder="DD-MM-YYYY"
              allowToday
              disablePastDates
              size="half"
              addonAfter={<i class="i i-date-range" />}
              disabled={internals._startsImmediately}
              placement="topLeft"
              onChange={props.onDateChange('start_at')}
              defaultValue={dateInMoment}
              readOnly
            />

            {!!fields.start_at && (
              <Input.TimePicker
                name="start_at_time"
                placeholder="HH:MM A"
                size="half"
                addonAfter={<i class="i i-time" />}
                disabled={internals._startsImmediately}
                onChange={props.onTimeChange('start_at_time')}
                defaultValue={dateInMoment}
                readOnly
              />
            )}
            <Description text="Date from which subscription should start" />
          </div>
        </Input.Group>

        <Input
          label="Total Count"
          type="number"
          description="No. of billing cycles to be charged"
          size="half"
          name="total_count"
          defaultValue={fields.total_count}
          validator={this.validateTotalCount}
          disabled={!selectedPlan}
          min={1}
          max={planPeriodToMaxCycleMap[(selectedPlan || {}).period]}
          required
        />
      </>
    );
  }
}

function PlanOption({ option }) {
  return (
    <div class="custom-powerselect-options">
      <p>{option.name}</p>
      <Amount value={option.amount} currency={option.currency} /> per unit
    </div>
  );
}

function getInformativeMessage({ interval, period, currency }) {
  return totalAmount => (
    <>
      {getIntervalCycle(interval, period)} customer will be charged{' '}
      <Amount value={totalAmount} currency={currency} />
    </>
  );
}

function getSelectedPlan(plans = [], fields = {}) {
  return plans.find(({ id }) => id === fields.plan_id);
}

function getPlans(plans) {
  return plans.items.map(plan => ({
    ...plan.item,
    ...plan,
    item: undefined,
  }));
}

var planPeriodToMaxCycleMap = {
  daily: 3650,
  weekly: 520,
  monthly: 120,
  yearly: 10,
};
