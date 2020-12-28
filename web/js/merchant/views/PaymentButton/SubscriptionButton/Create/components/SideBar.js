import { classList } from 'common/utils/rzp-utils';
import { filterSubscriptionPaymentItems } from 'merchant/reducers/subscriptionButtons/create';
import track from '../track';

import { totalTabs } from './Form';

export default class SideBar extends React.Component {
  totalTabs = totalTabs;

  get isButtonDetailsDone() {
    const { subscriptionButtonEntity, stepsProgress } = this.props;

    const hasTitle = subscriptionButtonEntity.title,
      hasButtonText = !!subscriptionButtonEntity.settings.payment_button_text;

    let hasButtonDetails = hasTitle && hasButtonText;

    return hasButtonDetails && stepsProgress.isButtonDetailsReviewed;
  }

  get isPlansDetailsDone() {
    const { paymentFields, stepsProgress } = this.props;

    const fields = filterSubscriptionPaymentItems(paymentFields, false);

    return fields && !!fields.length && stepsProgress.isPlansDetailsReviewed;
  }

  get isOneTimePaymentsDetailsDone() {
    const { stepsProgress } = this.props;

    // Only review is enough bcoz the fields are optional
    return stepsProgress.isOneTimePaymentsDetailsReviewed;
  }

  get isCustomerDetailsDone() {
    const { udfFields, stepsProgress } = this.props;

    return udfFields && !!udfFields.length && stepsProgress.isCustomerDetailsReviewed;
  }

  get totalTabsDone() {
    let totalStepsDone = 0;

    if (this.isButtonDetailsDone) {
      totalStepsDone++;
    }
    if (this.isPlansDetailsDone) {
      totalStepsDone++;
    }
    if (this.isOneTimePaymentsDetailsDone) {
      totalStepsDone++;
    }
    if (this.isCustomerDetailsDone) {
      totalStepsDone++;
    }

    return totalStepsDone;
  }

  render() {
    const { subscriptionButtonEntity } = this.props;
    const progressPercentage = subscriptionButtonEntity
      ? (this.totalTabsDone * 100) / this.totalTabs
      : 0;

    return (
      <div class="PaymentButton-Create-SideBar">
        <img src="/dist/css/assets/payment_button/sidebar-display.svg" />

        {subscriptionButtonEntity && (
          <React.Fragment>
            <div class="SideBar-title">
              {this.props.subscriptionButtonId ? 'Edit Progress' : 'Creation Progress'}
            </div>

            <ProgressBar
              title={`Step ${this.totalTabsDone}/${this.totalTabs}`}
              progressPercentage={progressPercentage}
              onClick={track.lj.trackOnClickProgressBar}
            />

            <ul class="SideBar-stepsList">
              <Step
                title="Button Details"
                isDone={this.isButtonDetailsDone}
                onClick={() => track.lj.trackOnClickProgressStep('button_details')}
              />

              <Step
                title="Add Subscription Plans"
                isDone={this.isPlansDetailsDone}
                onClick={() => track.lj.trackOnClickProgressStep('plans_details')}
              />

              <Step
                title="Add One-Time Payments"
                isDone={this.isOneTimePaymentsDetailsDone}
                onClick={() => track.lj.trackOnClickProgressStep('plans_details')}
              />

              <Step
                title="Customer Details"
                onClick={() => track.lj.trackOnClickProgressStep('customer_details')}
                isDone={this.isCustomerDetailsDone}
              />

              <Step
                title="Review and Create"
                description="Finalise configuration and create button"
                isDone={false}
                onClick={() => track.lj.trackOnClickProgressStep('review_create')}
                isDisabled={
                  !this.isButtonDetailsDone ||
                  !this.isPlansDetailsDone ||
                  !this.isOneTimePaymentsDetailsDone ||
                  !this.isCustomerDetailsDone
                }
              />
            </ul>
          </React.Fragment>
        )}
      </div>
    );
  }
}

const Step = ({ title, description, isDone, isDisabled, onClick }) => (
  <li
    class={classList('step', isDone && 'step--done', isDisabled && 'step--disabled')}
    onClick={onClick}
  >
    <span class="step-dot">
      <i class={`i ${isDisabled ? 'i-outline-lock' : 'i-check-circle'}`} />
    </span>

    <span class="step-title">
      {title}
      <div class="step-description">{description}</div>
    </span>
  </li>
);

const ProgressBar = ({ title, progressPercentage, onClick }) => (
  <div class="ProgressBar" onClick={onClick}>
    <div class="ProgressBar-title">{title}</div>
    <div class="ProgressBar-meter">
      <div
        class="ProgressBar-progress"
        style={{ transform: `scale(${progressPercentage / 100}, 1)` }}
      />
    </div>
  </div>
);
