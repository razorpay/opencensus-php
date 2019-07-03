import { TypeAhead } from 'react-power-select';

import Amount from 'rzp/ui/Amount';

import Input, { Label, Description } from 'component/Input';

import { getIntervalCycle } from 'rzp/utils/rzp-utils';

import QuantitySelector from '../New/QuantitySelector';

export default class NewSubscriptionLinkPlanDetails extends React.Component {
  static defaultProps = {
    isEdit: false,
  };

  constructor({ plans, fields }) {
    super();

    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields);
  }

  componentWillReceiveProps({ plans, fields }) {
    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields);
  }

  validateTotalCount = val => {
    if (!val) return 'Total Count is mandatory';

    if (val > planPeriodToMaxCycleMap[(this.selectedPlan || {}).period]) {
      return 'Billing cycles cannot exceed the period of 10 years';
    }
  };

  render() {
    const { fields, internals, ...props } = this.props,
      plans = this.plans,
      selectedPlan = this.selectedPlan;

    const dateInMoment = !!fields.start_at
      ? moment(fields.start_at, 'X')
      : undefined;

    let showStartDate = !props.isEdit,
      totalCountLabel = 'No of cycles to be updated';

    const isAuthenticatedSubscription =
      props.isEdit && props.status === 'authenticated';

    if (isAuthenticatedSubscription) {
      showStartDate = true;
    }

    const planPlaceholder = props.plans.loading
      ? 'Loading...'
      : 'Select a plan';

    return (
      <>
        <div class="Input Input--required">
          <Label text="Select Plan" />
          <div className="Input-content">
            <div className="Input-elWrapper">
              <TypeAhead
                options={plans}
                class="ps-in-modal"
                selected={selectedPlan}
                optionComponent={PlanOption}
                placeholder={planPlaceholder}
                disabled={props.plans.loading}
                onChange={props.onChangeInPlan}
                selectedOptionLabelPath="name"
                searchIndices={['id', 'name', 'description']}
              />
            </div>
            {fields.plan_id && (
              <QuantitySelector
                rate={selectedPlan.amount}
                quantity={fields.quantity}
                currency={selectedPlan.currency}
                informativeMessage={getInformativeMessage(selectedPlan)}
              />
            )}
          </div>
        </div>

        {showStartDate && (
          <React.Fragment>
            <Input.Check
              required
              label="Start Date"
              class="Input--vTop"
              data-name="_startsImmediately"
              disabled={props.isEdit && dateInMoment}
              checked={internals._startsImmediately}
              fieldLabel="Immediate, subscriptions starts with the first payment"
            />

            <Input.Group class="InputGroup--inline InputGroup--near">
              <div class="Input-content">
                <Input.ToCalendar
                  readOnly
                  allowToday
                  size="half"
                  name="start_at"
                  disablePastDates
                  placement="topLeft"
                  placeholder="DD-MM-YYYY"
                  defaultValue={dateInMoment}
                  disabled={internals._startsImmediately}
                  onChange={props.onDateChange('start_at')}
                  addonAfter={<i class="i i-date-range" />}
                />

                {!!fields.start_at && (
                  <Input.TimePicker
                    readOnly
                    size="half"
                    name="start_at_time"
                    placeholder="HH:MM A"
                    defaultValue={dateInMoment}
                    addonAfter={<i class="i i-time" />}
                    disabled={internals._startsImmediately}
                    onChange={props.onTimeChange('start_at_time')}
                  />
                )}
                <Description text="Date from which subscription should start" />
              </div>
            </Input.Group>
          </React.Fragment>
        )}

        <Input
          required
          min={1}
          size="half"
          type="number"
          disabled={!selectedPlan}
          validator={this.validateTotalCount}
          description="No. of billing cycles to be charged"
          max={planPeriodToMaxCycleMap[(selectedPlan || {}).period]}
          name={props.isEdit ? 'remaining_count' : 'total_count'}
          label={
            props.isEdit && !isAuthenticatedSubscription
              ? totalCountLabel
              : 'Total Count'
          }
          defaultValue={
            props.isEdit && !isAuthenticatedSubscription
              ? fields.remaining_count
              : fields.total_count
          }
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
  return plans.find(({ id }) => id === fields.plan_id) || {};
}

function getPlans(plans) {
  return plans.items.map(plan => ({
    ...plan.item,
    ...plan,
    item: undefined,
  }));
}

const planPeriodToMaxCycleMap = {
  daily: 3650,
  weekly: 520,
  monthly: 120,
  yearly: 10,
};
