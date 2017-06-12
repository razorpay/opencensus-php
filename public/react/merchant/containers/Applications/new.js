import { Component } from 'react';
import { connect } from 'react-redux';

import { Field, formValueSelector, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import InputField from 'rzp/ui/Forms/InputField';
import CheckboxField from 'rzp/ui/Forms/CheckboxField';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import { required } from 'rzp/utils/validators';

import { NavLink } from 'react-router-dom';
import Header from 'rzp/ui/Header';
import Spinner from 'rzp/ui/Spinner';
import * as ApplicationActions from 'merchant/modules/config';
import * as NotificationActions from 'rzp/modules/notifications';

const INFO = {
  icon: 'Your uploaded app icon will be shown to your users on Razorpay Connect screens. The icon will also be displayed in the connected applications list',
  dev: 'End-point on your development server that we\'ll redirect your users back to after they connect with Razorpay. Can be localhost. If you provide a comma-separated list, we will allow redirects to any of them via the redirect_uri parameter and default to the first one.',
  prod: 'End-point on your production server that we\'ll redirect your users back to after they connect with Razorpay. Must be HTTPS. If you provide a comma-separated list, we will allow redirects to any of them via the redirect_uri parameter and default to the first one.'
}

const selector = formValueSelector('newApplicationForm');
// @connect(state => {
//   return {
//     name: selector(state, 'name'),
//     website: selector(state, 'website'),
//   };
// }, null)
@reduxForm({
  name: 'newApplicationForm',
})
class NewApplicationForm extends Component {
  componentWillMount() {
    // this.props.fetchApplications
  }

  render() {
    // let { config, features, loading } = this.props.configState;

    return (

      <div class="content-wrapper">
        <div class="text-center content-wrapper">
          <div class="row">
            <div class="col-md-offset-2 col-md-10">
              <h4 class="form-header">Create Application</h4>
            </div>
          </div>
          <form class="form-horizontal" onSubmit={() => {}}>
            <Fieldset>

              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  Name
                </label>
                <div class="col-md-10">
                  <Field
                    name="name"
                    component={InputField}
                    class="form-control"
                    placeholder="Test App"
                    validate={[required()]}
                  />
                </div>
              </div>
              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  Website
                </label>
                <div class="col-md-10">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
              </div>

              <div class="form-group">
                <div className="col-md-offset-2 upload-container col-md-1">
                  <i class="fa fa-folder-open"></i>
                  <span>Upload App Icon</span>
                </div>
                <small class="col-md-9 help-block">
                  <i class="icon icon-info-circle" />
                  <span>
                    {INFO.icon}
                  </span>
                </small>
              </div>

              <div class="col-md-offset-2 col-md-10">
                <h5 class="form-header">Development</h5>
              </div>

              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  client_id
                </label>
                <div class="col-md-4">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
                <label class="col-md-2 control-label label-required">
                  client_secret
                </label>
                <div class="col-md-4">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  Redirect URIs
                </label>
                <div class="col-md-10">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
                <div class="clearfix"></div>
                <small class="col-md-offset-2 col-md-10 help-block">
                  <i class="icon icon-info-circle" />
                  <span>
                    {INFO.dev}
                  </span>
                </small>
              </div>

              <div class="col-md-offset-2 col-md-10">
                <h5 class="form-header">Production</h5>
              </div>

              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  client_id
                </label>
                <div class="col-md-4">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
                <label class="col-md-2 control-label label-required">
                  client_secret
                </label>
                <div class="col-md-4">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-2 control-label label-required">
                  Redirect URIs
                </label>
                <div class="col-md-10">
                  <Field
                    name="website"
                    component={InputField}
                    class="form-control"
                    placeholder="http://test-app.com/"
                    validate={[required()]}
                  />
                </div>
                <div class="clearfix"></div>
                <small class="col-md-offset-2 col-md-10 help-block">
                  <i class="icon icon-info-circle" />
                  <span>
                    {INFO.prod}
                  </span>
                </small>
              </div>

              <div class="form-group">
                <div class="col-md-offset-3 col-md-9">
                  <div class="btn-toolbar">
                    <AsyncButton
                      class="btn btn-primary pull-right"
                      text="Save"
                      pendingText="Saving..."
                      onClick={() => {}}
                    />

                    <AsyncButton
                      type="button"
                      class="btn btn-default pull-right"
                      text="Preview OAuth Page"
                      onClick={() => {}}
                    />
                  </div>
                </div>
              </div>
            </Fieldset>
          </form>
        </div>
      </div>
    );
  }
}

export default NewApplicationForm;