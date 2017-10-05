import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { withRouter } from 'react-router-dom';
import { Field, FieldArray, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import InputGroupField from 'rzp/ui/Forms/InputField/InputGroupField';
import Alert from 'rzp/ui/Forms/Alert';
import { required } from 'rzp/utils/validators';
import { savePlan } from 'merchant/modules/plans';
import { showNotification } from 'rzp/modules/notifications';
import NotesFieldArray from 'merchant/components/NotesFieldArray';
import FormItem from 'merchant/components/FormItem';

let Label = ({ text, htmlFor, required }) => {
  var classes = typeof required !== 'undefined' ? 'label-required' : '';

  return (
    <div class="pair-label">
      <label for={htmlFor} class={classes}>
        {text}
      </label>
    </div>
  );
};

@connect(null, {
  savePlan,
  showNotification,
})
@reduxForm({
  form: 'newPlan',
  initialValues: {
    period: 'monthly',
    interval: 1,
    item: {
      currency: 'INR',
    },
    notes: [],
  },
})
@withRouter
export default class AddPlan extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  state = {};

  componentWillMount() {
    if (this.props.plan) {
      this.props.initialize(this.props.plan);
    }
  }

  save = props => {
    return this.props
      .savePlan(props)
      .then(plan => {
        this.props.onSave(plan);
        this.props.history.push(`/plans/${plan[plan.resourceIdField]}`);
        this.props.showNotification({
          type: 'success',
          message: 'Plan saved successfully',
        });
      })
      .catch(err => {
        this.setState({
          errors: err.errors,
        });
      });
  };

  render() {
    const { handleSubmit, invalid, plan } = this.props;

    return (
      <div class="content-wrapper content-sm txn-details">
        <div class="panel panel-default SliderPanel">
          <div class="panel-heading">
            <i class="icon icon-plan text-main icon--formal" />{' '}
            <strong>{plan && plan.id ? 'Edit Plan' : 'New Plan'}</strong>
          </div>
          <div class="SliderPanel__Body">
            <form class="panel-body" onSubmit={handleSubmit(this.save)}>
              <FormItem
                label={_ => <Label text="Plan Name" required />}
                field={_ =>
                  <Field
                    name="item[name]"
                    component={InputField}
                    class="form-control"
                    autoFocus={true}
                    validate={required('Plan name is required')}
                    placeholder="The name known to your customers"
                  />}
              />
              <FormItem
                label={_ => <Label text="Plan Description" />}
                field={_ =>
                  <div>
                    <Field
                      name="item[description]"
                      component="textarea"
                      class="form-control"
                      placeholder="Optional"
                    />
                    <span class="help-block label--secondary">
                      <i class="icon icon-info-outline" />
                      The <b>Plan Name</b> and <b>Plan Description</b> will
                      appear on the invoice as entered above
                    </span>
                  </div>}
              />
              <FormItem
                label={_ => <Label text="Billing Frequency" required />}
                field={_ =>
                  <div class="billing-frequency">
                    <span>Every</span>
                    <Field
                      name="interval"
                      component="input"
                      class="form-control"
                      style={{
                        width: '38px',
                        padding: '6px 8px',
                        display: 'inline-block',
                        marginLeft: '8px',
                      }}
                      validate={required('Time period is required')}
                    />
                    <Field
                      name="period"
                      component="select"
                      class="form-control"
                      style={{
                        width: '98px',
                        padding: '6px 8px',
                        display: 'inline-block',
                        marginLeft: '8px',
                      }}
                    >
                      <option value="weekly">Week(s)</option>
                      <option value="monthly">Month(s)</option>
                      <option value="yearly">Year(s)</option>
                    </Field>

                    <span class="help-block label--secondary">
                      <i class="icon icon-info-outline" />You can set{' '}
                      <b>billing cycle</b> (start date and end date) and{' '}
                      <b>trial period</b> later while, creating a subscription.
                    </span>
                  </div>}
              />

              <FormItem
                label={_ => <Label text="Billing Amount" required />}
                field={_ =>
                  <div>
                    <Field
                      name="item[amount]"
                      component={InputGroupField}
                      prefix="INR"
                      suffix="per unit"
                      class="form-control"
                      validate={required('Billing amount is required')}
                      placeholder="000.00"
                    />
                    <span class="help-block label--secondary">
                      <i class="icon icon-info-outline" />
                      <b>Billing amount</b> and <b>billing frequency</b> can not
                      be changed later.
                    </span>
                  </div>}
              />

              <FormItem
                label={_ => <Label text="Internal Notes" />}
                field={_ =>
                  <FieldArray
                    name="notes"
                    component={NotesFieldArray}
                    required
                  />}
              />

              <Alert type="error" message={this.state.errors} />
              <div class="btn-toolbar text-center">
                <AsyncButton
                  type="submit"
                  class="btn btn-primary btn-half"
                  text="Create Plan"
                  pendingText="Creating..."
                  onClick={handleSubmit(this.save)}
                />
                <button
                  type="button"
                  class="btn btn-default btn-half"
                  onClick={() => {
                    this.context
                      .confirm({
                        header: 'Do you want to close this panel?',
                        message: 'Changes that you made may not be saved',
                        affirmativeLabel: 'Leave',
                        abortLabel: 'Stay',
                        action: () => this.props.history.push(`/plans`),
                      })
                      .catch(() => {});
                  }}
                >
                  Discard
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    );
  }
}

AddPlan.defaultProps = {
  onSave: () => {},
};
