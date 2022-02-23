import { Component, Fragment } from 'react';
import { Field, reduxForm } from 'redux-form';
import { compose, bindActionCreators } from 'redux';
import PopoverComponent, { PopoverBody } from 'common/ui/Popover';
import PropTypes from 'prop-types';
import InputField from 'common/ui/Forms/InputField';
import TableSlider from 'common/ui/TableSlider';
import AsyncButton from 'react-async-button';
import { openModal } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import ShowWhen from 'merchant/components/ShowWhen';
import ProcessingOptions from './ProcessingOptions';
import { required } from 'common/utils/validators';
import InstantRefundPricingTable from 'merchant/views/Transactions/Payments/components/InstantRefundPricingTable';
import { titleCase } from 'common/utils/rzp-utils';

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
    } = this.props;
    let ctaText = this.props.ctaText;
    if (batch_type_refund) {
      ctaText = 'Submit Batch';
    }

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
          <TableSlider
            title="Batch Entries"
            className="table-bordered batch-table"
            columns={getTableColumns(parsedEntries[0])}
            rows={parsedEntries}
            limit={limit}
            slideUnit={200}
          />
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
              onClick={handleSubmit(onCreateBatch)}
            />
          </form>
        </div>
      </div>
    );
  }
}

export default compose(
  reduxForm({
    form: 'createBatch',
  }),
  connect(
    (state) => ({
      user: state.session.user,
      refund_pricing: state.config.refund_pricing,
      features: state.config.features,
      default_refund_speed: state.config.config.default_refund_speed,
    }),
    (dispatch) => bindActionCreators({ openModal }, dispatch),
  ),
)(BatchCreateModal);
