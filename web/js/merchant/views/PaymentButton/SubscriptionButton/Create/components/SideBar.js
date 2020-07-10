import { classList } from 'common/utils/rzp-utils';
import track from '../track';

export default class SideBar extends React.Component {
  totalTabs = 4;

  get isButtonDetailsDone() {
    const { subscriptionButtonEntity, stepsProgress } = this.props;

    const hasTitle = subscriptionButtonEntity.title,
      hasButtonText = !!subscriptionButtonEntity.settings.payment_button_text;

    let hasButtonDetails = hasTitle && hasButtonText;

    return hasButtonDetails && stepsProgress.isButtonDetailsReviewed;
  }

  get isPlansDetailsDone() {
    const { planFields, stepsProgress } = this.props;

    return (
      planFields && !!planFields.length && stepsProgress.isPlansDetailsReviewed
    );
  }

  get isCustomerDetailsDone() {
    const { udfFields, stepsProgress } = this.props;

    return (
      udfFields && !!udfFields.length && stepsProgress.isCustomerDetailsReviewed
    );
  }

  get totalTabsDone() {
    let totalStepsDone = 0;

    if (this.isButtonDetailsDone) {
      totalStepsDone++;
    }
    if (this.isPlansDetailsDone) {
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
      ? this.totalTabsDone * 100 / this.totalTabs
      : 0;

    return (
      <div class="PaymentButton-Create-SideBar">
        <img src="/dist/css/assets/payment_button/sidebar-display.svg" />

        {subscriptionButtonEntity && (
          <React.Fragment>
            <div class="SideBar-title">
              {this.props.subscriptionButtonId
                ? 'Edit Progress'
                : 'Creation Progress'}
            </div>

            <ProgressBar
              title={`Step ${this.totalTabsDone}/${this.totalTabs}`}
              progressPercentage={progressPercentage}
              // onClick={track.lj.trackOnClickProgressBar}
            />

            <ul class="SideBar-stepsList">
              <Step
                title="Button Details"
                isDone={this.isButtonDetailsDone}
                // onClick={() => track.lj.trackOnClickProgressStep('button_details')}
              />

              <Step
                title="Plans Details"
                isDone={this.isPlansDetailsDone}
                // onClick={() => track.lj.trackOnClickProgressStep('plans_details')}
              />

              <Step
                title="Customer Details"
                // onClick={() => track.lj.trackOnClickProgressStep('customer_details')}
                isDone={this.isCustomerDetailsDone}
              />

              <Step
                title="Review and Create"
                description="Finalise configuration and create button"
                isDone={false}
                // onClick={() => track.lj.trackOnClickProgressStep('review_create' )}
                isDisabled={
                  !this.isButtonDetailsDone ||
                  !this.isPlansDetailsDone ||
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
    class={classList(
      'step',
      isDone && 'step--done',
      isDisabled && 'step--disabled'
    )}
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
