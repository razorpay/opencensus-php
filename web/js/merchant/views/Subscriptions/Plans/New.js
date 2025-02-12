import { Component } from 'react';
import PropTypes from 'prop-types';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { withRouter } from 'common/deprecated/withRouter';
import { Field, FieldArray, reduxForm, formValueSelector } from 'redux-form';

import Input from 'common/new-ui/Input';
import Alert from 'common/ui/Forms/Alert';
import InputField from 'common/ui/Forms/InputField';
import InputGroupField from 'common/ui/Forms/InputField/InputGroupField';
import {
  getKeysSeparatedByPipe,
  getEventCategoryFromPath,
  getURLQueryParams,
  paiseToRupees,
  getAmountFieldPlaceholder,
} from 'common/utils/rzp-utils';
import { selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { required, validateAmount } from 'common/utils/validators';
import FormItem from 'merchant/components/FormItem';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import { fetchPlan, savePlan } from 'merchant/reducers/plans';
import analytics from 'merchant/views/Subscriptions/analytics';
import { showNotification } from 'merchant_common/reducers/notifications';

import { trackSaveDuplicatePlan, trackSelectCurrency } from './ga';
import { compose } from 'redux';

const selector = formValueSelector('newPlan');

const Label = ({ text, htmlFor, required: isRequired }) => {
  const classes = typeof isRequired !== 'undefined' ? 'label-required' : '';

  return (
    <div className="pair-label">
      <label htmlFor={htmlFor} className={classes}>
        {text}
      </label>
    </div>
  );
};

class NewPlan extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};
  cloneOptions = { clone: '0' };
  UNSAFE_componentWillMount() {
    if (this.props.plan) {
      this.props.initialize(this.props.plan);
    }

    this.props.change('item[currency]', this.props.user.merchant.currency);

    this.fetchIfIntentDuplicate();
  }

  fetchIfIntentDuplicate() {
    const searchQuery = getURLQueryParams(this.props.location.search);
    if (searchQuery.duplicate_id) {
      this.props.fetchPlan(searchQuery.duplicate_id).then((data) => {
        this.isIntentDuplicate = true;
        this.cloneOptions = {
          clone: '1',
        };

        const newPlan = {};

        newPlan.item = {
          amount: paiseToRupees(data.item.amount),
          currency: data.item.currency,
          description: data.item.description,
          name: data.item.name,
        };

        newPlan.interval = data.interval;
        newPlan.period = data.period;

        newPlan.notes = Object.keys(data.notes).map((key) => ({
          key,
          value: data.notes[key],
        }));

        this.props.initialize(newPlan);
      });
    }
  }

  componentDidMount() {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    if (eventCategory) {
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Open Form - New Plan',
      });
    }
  }

  componentWillUnmount() {
    const { closeUrl } = this.props;
    const eventCategory = getEventCategoryFromPath(closeUrl);
    if (eventCategory) {
      window.rzpAnalytics?.({
        eventCategory,
        eventAction: 'Close Form - New Plan',
      });
    }
  }

  save = (props) => {
    if (this.isIntentDuplicate) {
      trackSaveDuplicatePlan();
    }

    return this.props
      .savePlan(props)
      .then((plan) => {
        const { closeUrl } = this.props;
        const eventCategory = getEventCategoryFromPath(closeUrl);

        if (eventCategory) {
          window.rzpAnalytics?.({
            eventCategory,
            eventAction: 'Submit Form - New Plan',
            eventLabel: getKeysSeparatedByPipe(props),
          });
        }
        this.props.onSave(plan);
        this.props.history.push(`/plans/${plan[plan.resourceIdField]}`);
        selfServeTrackSuccess({
          selfServeAction: 'Create New Plan',
          page: 'New Plans',
          screen: 'Subscriptions',
        });
        this.props.showNotification({
          type: 'success',
          message: 'Plan saved successfully',
        });
        analytics.track('plan.create.success', this.cloneOptions);
      })
      .catch((err) => {
        this.setState({
          errors: err.errors,
        });
        analytics.track('plan.create.fail', this.cloneOptions);
      });
  };

  onCurrencyChange = (option) => {
    trackSelectCurrency(option.name); // ISO format(INR)

    this.props.change('item[currency]', option.name);
  };

  onAddNotesClick = () => {
    analytics.track('plan.create.internal_notes', this.cloneOptions);
  };

  render() {
    const { handleSubmit, plan, currency } = this.props;
    let actionLabel = {};
    if (this.isIntentDuplicate) {
      actionLabel = {
        cancelInitiate: 'plan.clone.cancel.initiate',
        cancelStay: 'plan.clone.cancel.stay',
        cancelLeave: 'plan.clone.cancel.leave',
        createPlan: 'plan.clone.complete',
      };
    } else {
      actionLabel = {
        cancelInitiate: 'plan.create.cancel.initiate',
        cancelStay: 'plan.create.cancel.stay',
        cancelLeave: 'plan.create.cancel.leave',
        createPlan: 'plan.create.issue',
      };
    }
    return (
      <div className="content-wrapper content-sm txn-details plan-fields-wrapper">
        <div className="panel panel-default SliderPanel">
          <div className="panel-heading">
            <i className="i i-plan text-main icon--formal" />{' '}
            <strong>{plan && plan.id ? 'Edit Plan' : 'New Plan'}</strong>
          </div>
          <div className="SliderPanel__Body">
            <form className="panel-body" onSubmit={handleSubmit(this.save)}>
              <FormItem
                label={(_) => <Label text="Plan Name" required />}
                field={(_) => (
                  <Field
                    name="item[name]"
                    component={InputField}
                    className="form-control"
                    validate={required('Plan name is required')}
                    placeholder="The name known to your customers"
                    onBlur={() => analytics.track('plan.create.name', this.cloneOptions)}
                  />
                )}
              />
              <FormItem
                label={(_) => <Label text="Plan Description" />}
                field={(_) => (
                  <div>
                    <Field
                      name="item[description]"
                      component="textarea"
                      className="form-control"
                      placeholder="Optional"
                      onBlur={() => analytics.track('plan.create.description', this.cloneOptions)}
                    />
                    <span className="help-block label--secondary">
                      <i className="i i-info-outline" /> The <b>Plan Name</b> and{' '}
                      <b>Plan Description</b> will appear on the invoice as entered above
                    </span>
                  </div>
                )}
              />
              <FormItem
                label={(_) => <Label text="Billing Frequency" required />}
                field={(_) => (
                  <div className="billing-frequency">
                    <span>Every</span>
                    <Field
                      name="interval"
                      component="input"
                      className="form-control"
                      style={{
                        width: '38px',
                        padding: '6px 8px',
                        display: 'inline-block',
                        marginLeft: '8px',
                      }}
                      validate={required('Time period is required')}
                      onBlur={() => analytics.track('plan.create.interval', this.cloneOptions)}
                    />
                    <Field
                      name="period"
                      component="select"
                      className="form-control"
                      style={{
                        width: '98px',
                        padding: '6px 8px',
                        display: 'inline-block',
                        marginLeft: '8px',
                      }}
                      onBlur={() => analytics.track('plan.create.period', this.cloneOptions)}
                    >
                      <option value="daily">Days(s)</option>
                      <option value="weekly">Week(s)</option>
                      <option value="monthly">Month(s)</option>
                      <option value="yearly">Year(s)</option>
                    </Field>

                    <span className="help-block label--secondary">
                      <i className="i i-info-outline" /> You can set <b>billing cycle</b> (start
                      date and end date) and <b>trial period</b> later while, creating a
                      subscription.
                    </span>
                  </div>
                )}
              />

              <FormItem
                label={(_) => <Label text="Billing Amount" required />}
                field={(_) => (
                  <div className="Field-amount-wrapper">
                    <Input.CurrencySelect
                      name="currency"
                      onChange={this.onCurrencyChange}
                      defaultValue={currency}
                    />
                    <Field
                      name="item[amount]"
                      component={InputGroupField}
                      suffix="per unit"
                      className="form-control"
                      validate={[
                        required('Billing amount is required'),
                        (value) => validateAmount(value, null, currency),
                      ]}
                      placeholder={getAmountFieldPlaceholder(currency)}
                      onBlur={() => analytics.track('plan.create.amount', this.cloneOptions)}
                    />
                    <span className="help-block label--secondary">
                      <i className="i i-info-outline" />
                      <b>Billing amount</b> and <b>billing frequency</b> can not be changed later.
                    </span>
                  </div>
                )}
              />

              <FormItem
                label={(_) => <Label text="Internal Notes" />}
                field={(_) => (
                  <FieldArray
                    name="notes"
                    props={{
                      onAddNotesClick: this.onAddNotesClick,
                    }}
                    component={NotesFieldArray}
                    required
                  />
                )}
              />

              <div>
                <Alert type="error" message={this.state.errors} />
              </div>

              <div className="btn-toolbar text-center">
                <AsyncButton
                  type="submit"
                  className="btn btn-primary btn-half"
                  text="Create Plan"
                  pendingText="Creating..."
                  onClick={() => {
                    analytics.track(actionLabel.createPlan, this.cloneOptions);
                    handleSubmit(this.save);
                  }}
                />
                <button
                  type="button"
                  className="btn btn-half btn-outline"
                  onClick={() => {
                    analytics.track(actionLabel.cancelInitiate, this.cloneOptions);
                    this.context
                      .confirm({
                        header: 'Do you want to close this panel?',
                        message: 'Changes that you made may not be saved',
                        affirmativeLabel: 'Leave',
                        abortLabel: 'Stay',
                        action: () => {
                          analytics.track(actionLabel.cancelLeave, this.cloneOptions);
                          if (this.props.baseLocation) {
                            this.props.history.goBack();
                          } else {
                            this.props.history.push(`/plans`);
                          }
                        },
                        abort: () => {
                          analytics.track(actionLabel.cancelStay, this.cloneOptions);
                        },
                      })
                      .catch(() => {});
                  }}
                >
                  Cancel
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => {
      const { currency } = selector(state, 'item') || {
        currency: state.session.user.merchant.currency,
      };
      return {
        currency,
        baseLocation: state.app.baseLocation,
        user: state.session.user,
      };
    },
    {
      fetchPlan,
      savePlan,
      showNotification,
    },
  ),
  reduxForm({
    form: 'newPlan',
    initialValues: {
      period: 'monthly',
      interval: 1,
      item: {
        currency: 'INR',
      },
      notes: [],
    },
  }),
)(NewPlan);
