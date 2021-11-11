import moment from 'moment';
import { TypeAhead } from 'react-power-select';

import QuantitySelector from '../New/QuantitySelector';

import { UPI_AVL_LIMIT } from 'merchant/helpers/data';

import Amount from 'common/ui/Amount';
import Input, { Label, Description } from 'common/new-ui/Input';

import { getIntervalCycle, classList } from 'common/utils/rzp-utils';

const planPeriodToMaxCycleMap = {
  daily: 36500,
  weekly: 5200,
  monthly: 1200,
  yearly: 100,
};

export default class NewSubscriptionLinkPlanDetails extends React.Component {
  static defaultProps = {
    isEdit: false,
  };

  constructor({ plans, fields, offers }) {
    super();

    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields);
    this.selectedOffer = getSelectedOffer(offers.items, fields);
  }

  componentWillReceiveProps({ plans, fields, offers }) {
    this.plans = getPlans(plans);
    this.selectedPlan = getSelectedPlan(this.plans, fields);
    this.selectedOffer = getSelectedOffer(offers.items, fields);
  }

  validateTotalCount = (val) => {
    if (!val) {
      if (this.props.isEdit) {
        return 'No of remaining cycles';
      }

      return 'Total Count is mandatory';
    }

    if (val > planPeriodToMaxCycleMap[(this.selectedPlan || {}).period]) {
      return 'Billing cycles cannot exceed the period of 100 years';
    }
  };

  removeSelectedOffer = () => {
    this.props.onChangeInOffer();
  };

  render() {
    const { fields, internals, ...props } = this.props;
    const plans = this.plans;
    const selectedPlan = this.selectedPlan;

    const dateInMoment = !!fields.start_at ? moment(fields.start_at, 'X') : undefined;

    let showStartDate = !props.isEdit;
    const totalCountLabel = 'No of remaining cycles';

    const isAuthenticatedSubscription = props.isEdit && props.status === 'authenticated';

    if (isAuthenticatedSubscription) {
      showStartDate = true;
    }

    const planPlaceholder = props.plans.loading ? 'Loading...' : 'Select a plan';

    const offerPlaceholder = props.offers.loading
      ? 'Loading...'
      : 'Select an offer to provide discounts to consumers';

    const showUPIUnAvlBanner = selectedPlan.amount > UPI_AVL_LIMIT;

    return (
      <>
        <div class={classList('Input', !props.isEdit && 'Input--required')}>
          <Label text="Select Plan" />
          <div class="Input-content">
            <div class="Input-elWrapper">
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
              required={!props.isEdit}
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
          required={!props.isEdit}
          min={1}
          size="half"
          type="number"
          disabled={!selectedPlan}
          validator={this.validateTotalCount}
          description="No. of billing cycles to be charged"
          max={planPeriodToMaxCycleMap[(selectedPlan || {}).period]}
          name={props.isEdit ? 'remaining_count' : 'total_count'}
          const
          label={props.isEdit && !isAuthenticatedSubscription ? totalCountLabel : 'Total Count'}
          defaultValue={props.isEdit ? fields.remaining_count : fields.total_count}
        />

        {props.showOffers && (
          <div class="Input offer-selector">
            <Label text="Offer" />
            <div class="Input-content">
              <div class="Input-elWrapper">
                <TypeAhead
                  showClear={true}
                  options={props.offers.items}
                  selected={this.selectedOffer}
                  optionComponent={OfferOption}
                  placeholder={offerPlaceholder}
                  disabled={props.offers.loading}
                  onChange={props.onChangeInOffer}
                  selectedOptionLabelPath="name"
                  searchIndices={['id', 'name', 'terms']}
                />

                {fields.offer_id && (
                  <button onClick={this.removeSelectedOffer} class="btn btn-link">
                    {' '}
                    Remove{' '}
                  </button>
                )}
              </div>
              {fields.offer_id && (
                <div class="m-t terms">
                  {this.selectedOffer.display_text}
                  <p>{this.selectedOffer.terms}</p>
                </div>
              )}
            </div>
          </div>
        )}
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

function OfferOption({ option }) {
  return (
    <div class="custom-powerselect-options">
      <p>{option.display_text}</p>
    </div>
  );
}

function getInformativeMessage({ interval, period, currency }) {
  return (totalAmount) => (
    <>
      {getIntervalCycle(interval, period)} customer will be charged{' '}
      <Amount value={totalAmount} currency={currency} />
    </>
  );
}

function getSelectedPlan(plans = [], fields = {}) {
  return plans.find(({ id }) => id === fields.plan_id) || {};
}

function getSelectedOffer(offers = [], fields = {}) {
  return offers.find(({ id }) => id === fields.offer_id) || {};
}

function getPlans(plans) {
  return plans.items.map((plan) => ({
    ...plan.item,
    ...plan,
    item: undefined,
  }));
}
