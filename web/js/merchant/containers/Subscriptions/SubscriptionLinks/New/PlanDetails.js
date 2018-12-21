import { TypeAhead } from 'react-power-select';

import Amount from 'rzp/ui/Amount';

import Input, { Label, Description } from 'component/Input';

import { getIntervalCycle } from 'rzp/utils/rzp-utils';

import QuantitySelector from './QuantitySelector';

export default function NewSubscriptionLinkPlanDetails(props) {
  const plans = props.plans.items.map(plan => ({
    ...plan.item,
    ...plan,
    item: undefined,
  }));

  const selectedPlan = plans.find(({ id }) => id === props.selectedPlanId);

  return (
    <>
      <div class="Input Input--large Input--required">
        <Label text="Select Plan" />
        <div className="Input-content">
          <div className="Input-elWrapper">
            <TypeAhead
              options={plans}
              disabled={props.plans.loading}
              class="ps-in-modal"
              searchIndices={['id', 'name', 'description']}
              placeholder={props.plans.loading ? 'Loading...' : 'Select a plan'}
              optionComponent={PlanOption}
              selectedOptionLabelPath="name"
              onChange={props.onChangeInPlan}
              selected={selectedPlan}
            />
          </div>
        </div>
      </div>
      {props.selectedPlanId && (
        <QuantitySelector
          rate={selectedPlan.amount}
          quantity={props.planQuantity}
          informativeMessage={getInformativeMessage(selectedPlan)}
        />
      )}

      <Input.Check
        label="Start Date"
        fieldLabel="Immediate, subscriptions starts with the first payment"
      />

      <Input.Group class="InputGroup--inline InputGroup--near">
        <div class="Input-content">
          <Input.ToCalendar
            name="startAt"
            placeholder="DD-MM-YYYY"
            allowToday
            disablePastDates
            size="half"
            addonAfter={<i class="i i-date-range" />}
            disabled={false}
            placement="topLeft"
          />

          <Input.TimePicker
            name="startAtTime"
            placeholder="HH:MM A"
            size="half"
            addonAfter={<i class="i i-time" />}
          />
          <Description text="Date from which subscription should start" />
        </div>
      </Input.Group>

      <Input
        label="Total Count"
        type="number"
        description="No. of billing cycles to be charged"
        size="half"
      />
    </>
  );
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
