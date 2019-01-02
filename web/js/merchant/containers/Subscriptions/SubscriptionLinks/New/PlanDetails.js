import { TypeAhead } from 'react-power-select';

import Amount from 'rzp/ui/Amount';

import Input, { Label, Description } from 'component/Input';
import Button from 'component/Button';

import { getIntervalCycle } from 'rzp/utils/rzp-utils';

import QuantitySelector from './QuantitySelector';

export default function NewSubscriptionLinkPlanDetails({
  fields,
  internals,
  ...props
}) {
  const plans = props.plans.items.map(plan => ({
    ...plan.item,
    ...plan,
    item: undefined,
  }));

  const selectedPlan = plans.find(({ id }) => id === fields.plan_id);

  const dateInMoment = !!fields.start_at
    ? moment(fields.start_at, 'X')
    : undefined;

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
      {fields.plan_id && (
        <QuantitySelector
          rate={selectedPlan.amount}
          quantity={fields.quantity}
          informativeMessage={getInformativeMessage(selectedPlan)}
          readOnly
        />
      )}

      <Input.Check
        label="Start Date"
        fieldLabel="Immediate, subscriptions starts with the first payment"
        class="Input--vTop"
        data-name="_startsImmediately"
        checked={internals._startsImmediately}
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
        min={1}
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
