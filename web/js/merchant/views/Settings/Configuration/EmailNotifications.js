import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'common/ui/Forms/InputField';
import { showNotification } from 'merchant_common/reducers/notifications';
import { required } from 'common/utils/validators';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

@connect((state) => state.config, { showNotification })
@reduxForm({})
export default class EmailNotifications extends Component {
  UNSAFE_componentWillMount() {
    this.props.initialize(this.props.config);
  }

  onSave = (e) => {
    this.analytics();
    this.props.handleSubmit(this.props.onSave)(e);
  };

  analytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Email Notifications Addresses',
    });
    analyticsTrack({
      objectName: 'documentation',
      actionName: 'clicked',
      screen: 'home page',
      properties: {
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  render() {
    return (
      <div class="panel panel-default ftx-parent">
        <div class="panel-heading">
          <span class="title">Email Notifications</span>
        </div>

        <div class="panel-body">
          <form class="form-horizontal" onSubmit={this.onSave}>
            <div class="description">
              Enter email addresses that will receive email notifications regarding payments,
              settlements, daily payment reports, webhooks, etc. (You can enter multiple email
              addresses separated by a comma.)
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
                  class="btn btn-primary pull-right"
                  text="Save Changes"
                  pendingText="Saving..."
                  onClick={this.onSave}
                />
              </div>
            </div>
          </form>
        </div>
      </div>
    );
  }
}
