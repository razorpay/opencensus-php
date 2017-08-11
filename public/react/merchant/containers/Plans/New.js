import { Component, PropTypes } from 'react';
import { connect } from 'react-redux';
import { Field, FieldArray, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import InputGroupField from 'rzp/ui/Forms/InputField/InputGroupField';
import Alert from 'rzp/ui/Forms/Alert';
import ModalHeader from 'rzp/ui/ModalHeader';
import { required } from 'rzp/utils/validators';
import { savePlan } from 'merchant/modules/plans';
import * as ModalActions from 'rzp/modules/modals';
import { showNotification } from 'rzp/modules/notifications';
import NotesFieldArray from 'merchant/components/NotesFieldArray';

@connect(null, {
  savePlan,
  showNotification,
  ...ModalActions,
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
export default class AddPlan extends Component {
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
      <div class="plan-create">
        <ModalHeader
          title={plan && plan.id ? 'Edit Plan' : 'New Plan'}
          onCloseClick={this.props.closeModal}
        />

        <div class="modal-body">
          <Alert type="error" message={this.state.errors} />

          <form class="form-horizontal" onSubmit={handleSubmit(this.save)}>
            <div class="form-group">
              <label class="col-sm-3 control-label label-required">
                Plan Name
              </label>
              <div class="col-sm-7">
                <Field
                  name="item[name]"
                  component={InputField}
                  class="form-control"
                  autoFocus={true}
                  validate={required()}
                />
              </div>
            </div>

            <div class="form-group">
              <label class="control-label col-sm-3">Plan Description</label>
              <div class="col-sm-7">
                <Field
                  name="item[description]"
                  component="textarea"
                  class="form-control"
                />
              </div>
              <div class="col-sm-offset-3 col-sm-7">
                <small class="help-block">
                  <i class="icon icon-info-circle" />
                  The
                  {' '}
                  <b>Plan Name</b>
                  {' '}
                  and
                  {' '}
                  <b>Plan Description</b>
                  {' '}
                  will appear on the invoice as entered above
                </small>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label label-required">
                Billing Frequency
              </label>
              <div class="col-sm-7">
                <div class="billing-frequency">
                  <span>Every</span>
                  <Field
                    name="interval"
                    component={InputField}
                    class="form-control"
                    validate={required()}
                  />
                  <Field name="period" component="select" class="form-control">
                    <option value="weekly">Week(s)</option>
                    <option value="monthly">Month(s)</option>
                    <option value="yearly">Year(s)</option>
                  </Field>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label label-required">
                Billing Amount
              </label>
              <div class="col-sm-7">
                <Field
                  name="item[amount]"
                  component={InputGroupField}
                  prefix="INR"
                  suffix="per unit"
                  class="form-control"
                  validate={required()}
                />
              </div>
            </div>

            <div class="form-group">
              <label class="col-sm-3 control-label">
                Internal Notes
              </label>
              <div class="col-sm-7">
                <FieldArray name="notes" component={NotesFieldArray} />
              </div>
            </div>

            <div class="row Modal__actions">
              <div class="col-sm-offset-3 col-sm-7">
                <div class="btn-toolbar">
                  <button
                    type="button"
                    class="btn btn-default"
                    onClick={this.props.closeModal}
                  >
                    Cancel
                  </button>

                  <AsyncButton
                    type="submit"
                    class="btn btn-primary"
                    text="Create Plan"
                    pendingText="Creating..."
                    onClick={handleSubmit(this.save)}
                  />
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

AddPlan.defaultProps = {
  onSave: () => {},
};
