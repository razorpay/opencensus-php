import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import { showNotification } from 'rzp/modules/notifications';
import { required } from 'rzp/utils/validators';

@connect(state => state.config, { showNotification })
@reduxForm()
export default class EmailNotifications extends Component {
  componentWillMount() {
    this.props.initialize(this.props.config);
  }

  render() {
    let { handleSubmit, onSave } = this.props;
    return (
      <div class="panel panel-default">
        <div class="panel-heading">Email Notifications</div>

        <div class="panel-body">
          <form class="form-horizontal" onSubmit={handleSubmit(onSave)}>
            <div class="help-block">
              Enter email addresses that will receive email notifications regarding payments, settlements, daily payment reports, webhooks, etc. (You can enter multiple email addresses separated by a comma.)
            </div>

            <div class="form-group">
              <div class="col-sm-10">
                <Field
                  name="transaction_report_email"
                  component={InputField}
                  class="form-control"
                  maxLength="255"
                  validate={required()}
                />
              </div>

              <div class="col-sm-2">
                <AsyncButton
                  class="btn btn-default pull-right"
                  text="Save Changes"
                  pendingText="Saving..."
                  onClick={handleSubmit(onSave)}
                />
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
