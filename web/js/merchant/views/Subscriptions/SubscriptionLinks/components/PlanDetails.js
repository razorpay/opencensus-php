import React from 'react';
import moment from 'moment';
import { TypeAhead } from 'react-power-select';
import Amount from 'common/ui/Amount';
import Alert from 'common/ui/Forms/Alert';
import { DocLink } from 'merchant/components/DocsLink';
import Input, { Label, Description } from 'common/new-ui/Input';
import { getIntervalCycle, classList } from 'common/utils/rzp-utils';

import QuantitySelector from 'merchant/views/Subscriptions/SubscriptionLinks/New/QuantitySelector';
import analytics from 'merchant/views/Subscriptions/analytics';

const planPeriodToMaxCycleMap = {
  daily: 36500,
  weekly: 5200,
  monthly: 1200,
  yearly: 100,
};

// eslint-disable-next-line react/no-unsafe
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

  UNSAFE_componentWillReceiveProps({ plans, fields, offers }) {
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

    return '';
  };

  removeSelectedOffer = () => {
    this.props.onChangeInOffer();
    analytics.track('subscription.create.removeoffer', this.props.cloneOptions);
  };

  render() {
    const { fields, internals, ...props } = this.props;
    const plans = this.plans;
    const selectedPlan = this.selectedPlan;
    const dateInMoment = !!fields.start_at ? moment(fields.start_at, 'X') : undefined;
    const totalCountLabel = 'No of remaining cycles';
    const isAuthenticatedSubscription = props.isEdit && props.status === 'authenticated';
    let showStartDate = !props.isEdit;

    if (isAuthenticatedSubscription) {
      showStartDate = true;
    }

    const planPlaceholder = props.plans.loading ? 'Loading...' : 'Select a plan';
    const offerPlaceholder = props.offers.loading
      ? 'Loading...'
      : 'Select an offer to provide discounts to consumers';

    let eventLabel = {};
    if (props.isEdit) {
      eventLabel = {
        selectedPlan: 'subscription.update.plan',
        quantity: 'subscription.update.quantity',
        billingCycles: 'subscription.update.remaining_cycles',
      };
    } else {
      eventLabel = {
        selectedPlan: 'subscription.create.select_plan',
        quantity: 'subscription.create.quantity',
        billingCycles: 'subscription.create.total_cycles',
      };
    }
    return (
      <>
        {/* Adding alert & disabling fields for edit subscription page if subs is with domestic card or upi */}
        {props.disableEdit && props.isEdit && (
          <div>
            <Alert
              class="alert-sm"
              type="warning"
              message={
                <>
                  For this subscription, only the offer can be updated.{' '}
                  <DocLink
                    class="btn-link"
                    href="https://razorpay.com/docs/payments/subscriptions/update/"
                    target="_blank"
                  >
                    Know more.
                  </DocLink>
                </>
              }
              showDismiss={false}
            />
          </div>
        )}
        <div class={classList('Input', !props.isEdit && 'Input--required')}>
          <Label text="Select Plan" />
          <div class="Input-content">
            <div class="Input-elWrapper">
              <TypeAhead
                // Exclusively pass showClear as false so that in the DOM, PowerSelect__Clear element is removed
                showClear={false}
                options={plans}
                class="ps-in-modal"
                selected={selectedPlan}
                optionComponent={PlanOption}
                placeholder={planPlaceholder}
                disabled={props.plans.loading || props.disableEdit}
                onChange={(...args) => {
                  props.onChangeInPlan(...args);
                  analytics.track(eventLabel.selectedPlan, this.props.cloneOptions);
                }}
                selectedOptionLabelPath="name"
                searchIndices={['id', 'name', 'description']}
              />
            </div>
            {fields.plan_id && (
              <QuantitySelector
                rate={selectedPlan.amount}
                quantity={fields.quantity}
                disabled={props.disableEdit}
                currency={selectedPlan.currency}
                informativeMessage={getInformativeMessage(selectedPlan)}
                onBlur={() => {
                  analytics.track(eventLabel.quantity, this.props.cloneOptions);
                }}
              />
            )}
          </div>
        </div>

        {showStartDate && (
          <>
            <Input.Check
              required={!props.isEdit}
              label="Start Date"
              class="Input--vTop"
              data-name="_startsImmediately"
              disabled={props.isEdit && dateInMoment}
              checked={internals._startsImmediately}
              fieldLabel="Immediate, subscriptions starts with the first payment"
              onBlur={() => {
                analytics.track(
                  'subscription.create.start_date_immediate',
                  this.props.cloneOptions,
                );
              }}
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
                  onBlur={() => {
                    analytics.track(
                      'subscription.create.start_date_trial',
                      this.props.cloneOptions,
                    );
                  }}
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
                    onBlur={() => {
                      analytics.track(
                        'subscription.create.start_time_trial',
                        this.props.cloneOptions,
                      );
                    }}
                  />
                )}
                <Description text="Date from which subscription should start" />
              </div>
            </Input.Group>
          </>
        )}

        <Input
          required={!props.isEdit}
          min={1}
          size="half"
          type="number"
          disabled={!selectedPlan || props.disableEdit}
          validator={this.validateTotalCount}
          description="No. of billing cycles to be charged"
          max={planPeriodToMaxCycleMap[(selectedPlan || {}).period]}
          name={props.isEdit ? 'remaining_count' : 'total_count'}
          label={props.isEdit && !isAuthenticatedSubscription ? totalCountLabel : 'Total Count'}
          defaultValue={props.isEdit ? fields.remaining_count : fields.total_count}
          onBlur={() => {
            analytics.track(eventLabel.billingCycles, this.props.cloneOptions);
          }}
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
                  onChange={(...args) => {
                    props.onChangeInOffer(...args);
                    analytics.track('subscription.create.addoffer', this.props.cloneOptions);
                  }}
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
