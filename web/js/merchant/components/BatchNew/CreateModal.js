import { Component, Fragment } from 'react';
import { Field, reduxForm } from 'redux-form';
import { compose, bindActionCreators } from 'redux';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import PropTypes from 'prop-types';
import InputField from 'common/ui/Forms/InputField';
import TableSlider from 'common/ui/TableSlider';
import AsyncButton from 'react-async-button';
import { openModal, closeModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import ShowWhen from 'merchant/components/ShowWhen';
import ProcessingOptions from './ProcessingOptions';
import { required } from 'common/utils/validators';
import InstantRefundPricingTable from 'merchant/views/Transactions/v1/Payments/components/InstantRefundPricingTable';
import { titleCase, is2faRouteExperimentEnabled } from 'common/utils/rzp-utils';
import {
  triggerOtpOnEmail,
  triggerOtpOnSMS,
  triggerOtpOnBoth,
} from 'merchant_common/reducers/twoFactor';
import TwoFactorVerificationOTP from 'common/ui/TwoFactorVerification/TwoFactorVerificationOTP';
import { showNotification } from 'merchant_common/reducers/notifications';
import { withSplitzService } from 'common/splitz';

const getTableColumns = (entries) => {
  return Object.keys(entries).map((entry) => ({
    title: entry,
    value: (item) => item[entry],
  }));
};

class BatchCreateModal extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  static defaultProps = {
    ctaText: 'Create',
    pendingText: 'Creating...',
  };

  //shift input caret to the end
  moveCaretAtEnd(e) {
    const temp_value = e.target.value;
    e.target.value = '';
    e.target.value = temp_value;
  }
  onOtpConfirm = ({ otp }) => {
    const value = this.props.onCreateBatch({ ...this.props.initialValues, otp });
    return Promise.resolve(value);
  };

  onCloseClick = () => {
    this.props.closeModal();
  };

  getOTPDestination = () => {
    const { user } = this.props.user;
    const hasOnlyEmail = Boolean(user.email && user.confirmed);
    const hasOnlyMobile = Boolean(user.contact_mobile && user.contact_mobile_verified);
    const hasBothEmailAndMobile = hasOnlyEmail && hasOnlyMobile;

    return {
      hasOnlyEmail,
      hasOnlyMobile,
      hasBothEmailAndMobile,
    };
  };

  show2faModal = () => {
    const { user } = this.props.user;
    const { hasBothEmailAndMobile, hasOnlyEmail, hasOnlyMobile } = this.getOTPDestination();

    const isNewAccountAndSettingsPage = this.props.user?.isAccountAndSettingsRevampEnabled;

    this.props.openModal({
      size: 'small',
      component: (
        <TwoFactorVerificationOTP
          onConfirm={this.onOtpConfirm}
          onClose={this.onCloseClick}
          onResend={this.sendVerificationOtp(true)}
          title="Invite new member"
          renderMessage={() => (
            <p class="m-b">
              Inviting new member requires you to enter OTP sent over to your{' '}
              {hasOnlyEmail && (
                <>
                  registered email address <strong>{user.email}</strong>
                </>
              )}
              {hasBothEmailAndMobile && ' and '}
              {hasOnlyMobile && (
                <>
                  registered phone number <strong>{user.contact_mobile}</strong>
                </>
              )}
            </p>
          )}
          isNewAccountAndSettingsPage={isNewAccountAndSettingsPage}
        />
      ),
    });
  };

  sendVerificationOtp = (isResend = false) => {
    return () => {
      const { hasBothEmailAndMobile, hasOnlyEmail } = this.getOTPDestination();

      let triggerOTP;
      if (hasBothEmailAndMobile) {
        triggerOTP = triggerOtpOnBoth;
      } else if (hasOnlyEmail) {
        triggerOTP = triggerOtpOnEmail;
      } else triggerOTP = triggerOtpOnSMS;

      return triggerOTP()
        .then(() => {
          // Dont show when 2fa modal is already being shown
          if (!isResend) {
            this.show2faModal();
          }
        })
        .catch(({ errors }) => {
          this.props.showNotification({
            type: 'error',
            message: errors,
          });
        });
    };
  };

  render() {
    const batch_type_refund = this.props.batchType == 'refund';
    let limit = 3;
    if (batch_type_refund) {
      limit = 200;
    }
    const {
      parsedEntries,
      onCreateBatch,
      speedCount,
      processableCount,
      handleSubmit,
      pendingText,
      children,
      onFileNameTrack = () => {},
      onPreview = () => {},
      isCreatingBatch,
    } = this.props;
    let ctaText = this.props.ctaText;
    if (batch_type_refund) {
      ctaText = 'Submit Batch';
    }

    const { abExperiments } = this.props.splitz;

    const is2faExperimentActive = is2faRouteExperimentEnabled(abExperiments);

    return (
      <div class={`modal-body ${batch_type_refund ? 'batch-refund-create-modal' : ''}`}>
        <div onClick={this.props.trackSampleInterpretation}>
          {batch_type_refund && (
            <div class="panel create-modal-panel">
              <div class="panel-header">
                <h3>
                  <img
                    style={{ marginRight: '8px' }}
                    src="https://cdn.razorpay.com/static/assets/success-tick-circle.svg"
                  />
                  You have uploaded batch of {processableCount} payments.
                </h3>
              </div>
              <div class="panel-body" style={{ paddingBottom: '8px', paddingLeft: '8px' }}>
                <ul>
                  {speedCount && speedCount.normal ? (
                    <li>
                      <b>
                        {speedCount.normal} Payment{speedCount.normal > 1 ? 's' : ''} will be
                        refunded with Normal Speed
                      </b>
                    </li>
                  ) : null}
                  {speedCount && speedCount.optimum ? (
                    <li>
                      <b>
                        {speedCount.optimum} Payment{speedCount.optimum > 1 ? 's' : ''} will be
                        refunded with Instant(Optimum) Speed*
                      </b>
                    </li>
                  ) : null}
                  {speedCount && speedCount.default ? (
                    <li>
                      <b>
                        {speedCount.default} Payment{speedCount.default > 1 ? 's' : ''} will be
                        refunded with Default Refund Speed -{' '}
                        {this.props.default_refund_speed === 'normal'
                          ? 'Normal'
                          : 'Instant(Optimum)'}{' '}
                        Speed{this.props.default_refund_speed === 'normal' ? '' : '*'}{' '}
                        <span>
                          <i class="i i-help" />
                          <PopoverComponent
                            align="right"
                            theme="dark"
                            parentQuerySelector=".Modal--large"
                          >
                            <PopoverBody>
                              {speedCount.default} payment{speedCount.default > 1 ? 's' : ''} in the
                              file have no specified speed. These will be processed by the default
                              refund speed which is{' '}
                              {this.props.default_refund_speed == 'normal' ? 'normal' : 'instant'}{' '}
                              for your account.
                            </PopoverBody>
                          </PopoverComponent>
                        </span>
                      </b>
                    </li>
                  ) : null}
                  {speedCount.optimum > 0 ||
                  (speedCount.default && this.props.default_refund_speed !== 'normal') ? (
                    <div>
                      *We charge minimal processing fee on instant refunds,{' '}
                      <strong
                        onClick={() => {
                          window.rzpAnalytics?.({
                            eventCategory: `Batch ${titleCase(this.props.batchType)}`,
                            eventAction: 'Check pricing - upload preview modal',
                            eventLabel: `Check pricing`,
                          });
                          this.context.confirm({
                            header: (
                              <div
                                style={{
                                  marginTop: 0,
                                  fontSize: '19px',
                                  marginBottom: 0,
                                }}
                              >
                                <i style={{ marginRight: '5px' }} class="i i-instant-refund" /> Fee
                                for Instant Refund
                              </div>
                            ),
                            message: () => (
                              <InstantRefundPricingTable pricing={this.props.refund_pricing} />
                            ),
                            abortLabel: 'Close',
                            affirmativeLabel: 'Got It!',
                            abort: this.abort,
                            action: this.abort,
                          });
                        }}
                        style={{
                          color: 'rgb(82, 143, 240)',
                          cursor: 'pointer',
                          marginTop: '12px',
                        }}
                        class="highlight"
                      >
                        check pricing
                      </strong>
                      .
                    </div>
                  ) : null}
                </ul>
              </div>
            </div>
          )}
          <p>This is how we are interpreting your data.</p>
          <div onMouseEnter={onPreview}>
            <TableSlider
              title="Batch Entries"
              className="table-bordered batch-table"
              columns={getTableColumns(parsedEntries[0])}
              rows={parsedEntries}
              limit={limit}
              slideUnit={200}
            />
          </div>
        </div>
        <div class="modal-info stretch create">
          <form
            onSubmit={() => {
              let label = [];
              Object.keys(speedCount).forEach((k) => {
                if (speedCount[k] > 0) {
                  label.push(k);
                }
              });
              label = label.join(', ');
              window.rzpAnalytics?.({
                eventCategory: `Batch ${titleCase(this.props.batchType)}`,
                eventAction: 'Issue Refund - upload preview modal',
                eventLabel: `Click to upload file`,
              });
              return handleSubmit(onCreateBatch);
            }}
          >
            {
              <Fragment>
                <h5 class="file-name-head">
                  <strong>
                    BATCH FILE NAME{' '}
                    <i
                      class="i i-info-circle m-l"
                      title="Maximum filename length is 255 characters."
                    />
                  </strong>
                </h5>
                <div class="form-group">
                  <Field
                    name="name"
                    key="field"
                    component={InputField}
                    class="form-control"
                    autoFocus={true}
                    validate={[required()]}
                    maxLength="255"
                    onFocus={this.moveCaretAtEnd}
                    onChange={onFileNameTrack}
                  />
                </div>
              </Fragment>
            }

            <ShowWhen
              additionalCondition={(user) => user.isBatchSchedulingOptionsExperimentEnabled}
            >
              {this.props.processingOptions ? <ProcessingOptions /> : null}
            </ShowWhen>

            {/* extra fields sent with create batch */}
            {children}
            {batch_type_refund ? (
              <p class="text-center">
                <i className="i i-info-circle" /> Once the batch file is submitted, it will be
                processed after 70 mins.
              </p>
            ) : null}

            <AsyncButton
              type="button"
              class={`btn btn-primary ${batch_type_refund ? 'process-refunds-btn' : ''}`}
              text={ctaText}
              pendingText={pendingText}
              onClick={
                is2faExperimentActive ? this.sendVerificationOtp() : handleSubmit(onCreateBatch)
              }
              disabled={isCreatingBatch}
            />
          </form>
        </div>
      </div>
    );
  }
}

export default withSplitzService(
  compose(
    reduxForm({
      form: 'createBatch',
    }),
    connect(
      (state) => ({
        user: state.session.user,
        refund_pricing: state.config?.refund_pricing || {},
        default_refund_speed: state.config?.config?.default_refund_speed,
      }),
      (dispatch) => bindActionCreators({ openModal, closeModal, showNotification }, dispatch),
    ),
  )(BatchCreateModal),
);
