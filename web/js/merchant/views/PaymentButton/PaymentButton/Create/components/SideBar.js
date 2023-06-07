import React from 'react';
import ShowWhen from 'merchant/components/ShowWhen';
import { classList } from 'common/utils/rzp-utils';
import { templateTypes } from 'merchant/views/PaymentButton/PaymentButton/Create/components/Templates/meta';
import track from 'merchant/views/PaymentButton/PaymentButton/Create/track';

import SidebarImageRzp from 'assets/payment_button/sidebar-display.svg';
import SidebarImageCurlec from 'assets/payment_button/sidebar-display-curlec.svg';

import { ORG_CUSTOM_CODE_MAP } from 'merchant/models/User';

const PAYMENT_BTN_SIDEBAR_IMG = {
  [ORG_CUSTOM_CODE_MAP.RAZORPAY]: SidebarImageRzp,
  [ORG_CUSTOM_CODE_MAP.CURLEC]: SidebarImageCurlec,
};

export default class SideBar extends React.Component {
  get isQuickPayTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.quickPay.key;
  }

  get isDonationsTemplate() {
    const { paymentButtonEntity } = this.props;
    const templateType = paymentButtonEntity.settings.payment_button_template_type;

    return templateType === templateTypes.donation.key;
  }

  get isButtonDetailsDone() {
    const { paymentButtonEntity, amountFields, stepsProgress } = this.props;

    const hasTitle = paymentButtonEntity.title;
    const hasButtonText = !!paymentButtonEntity.settings.payment_button_text;

    let hasButtonDetails = hasTitle && hasButtonText;

    if (this.isQuickPayTemplate) {
      hasButtonDetails = hasButtonDetails && amountFields && !!amountFields.length;
    }

    return hasButtonDetails && stepsProgress.isButtonDetailsReviewed;
  }

  get isAmountDetailsDone() {
    const { amountFields, stepsProgress } = this.props;

    return amountFields && !!amountFields.length && stepsProgress.isAmountDetailsReviewed;
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
    if (!this.isQuickPayTemplate && this.isAmountDetailsDone) {
      totalStepsDone++;
    }
    if (this.isCustomerDetailsDone) {
      totalStepsDone++;
    }

    if (this.props.isSuccessViewOpened) {
      totalStepsDone++;
    }

    return totalStepsDone;
  }

  get totalTabs() {
    return this.isQuickPayTemplate ? 3 : 4;
  }

  render() {
    const { isSuccessViewOpened, isSuccessViewOpenedForExistingId, orgCustomCode } = this.props;
    const progressPercentage = (this.totalTabsDone * 100) / this.totalTabs;
    const sidebarImage =
      PAYMENT_BTN_SIDEBAR_IMG[orgCustomCode] ||
      PAYMENT_BTN_SIDEBAR_IMG[ORG_CUSTOM_CODE_MAP.RAZORPAY];

    return (
      <div class="PaymentButton-Create-SideBar">
        <ShowWhen additionalCondition={(user) => !user.isOrgAxis}>
          <img src={sidebarImage} />
        </ShowWhen>

        <div class="SideBar-title">
          {this.props.paymentButtonId && (!isSuccessViewOpened || isSuccessViewOpenedForExistingId)
            ? 'Edit Progress'
            : 'Creation Progress'}
        </div>

        <ProgressBar
          title={`Step ${this.totalTabsDone}/${this.totalTabs}`}
          progressPercentage={progressPercentage}
          onClick={track.onClickProgressBar}
        />

        <ul class="SideBar-stepsList">
          <Step
            title="Button Details"
            isDone={this.isButtonDetailsDone}
            onClick={() => track.onClickProgressStep('button_details')}
          />

          {!this.isQuickPayTemplate && (
            <Step
              title={this.isDonationsTemplate ? 'Donation Amount' : 'Amount Details'}
              isDone={this.isAmountDetailsDone}
              onClick={() =>
                track.onClickProgressStep(
                  this.isDonationsTemplate ? 'donation_amount' : 'amount_details',
                )
              }
            />
          )}

          <Step
            title={this.isDonationsTemplate ? 'Donor Details' : 'Customer Details'}
            description={this.isDonationsTemplate ? 'Ask email, contact, etc. before payment' : ''}
            onClick={() => track.onClickProgressStep('customer_details')}
            isDone={this.isCustomerDetailsDone}
          />

          <Step
            title="Review and Create"
            description="Finalise configuration and create button"
            isDone={isSuccessViewOpened}
            onClick={() => track.onClickProgressStep('review_create')}
            isDisabled={
              !this.isButtonDetailsDone ||
              (!this.isQuickPayTemplate && !this.isAmountDetailsDone) ||
              !this.isCustomerDetailsDone
            }
          />
        </ul>
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
