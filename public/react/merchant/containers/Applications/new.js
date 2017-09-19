import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, formValueSelector, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { Link, withRouter } from 'react-router-dom';
import { required, isUrl } from 'rzp/utils/validators';
import InputField from 'rzp/ui/Forms/InputField';
import TaggedInput from 'rzp/ui/Forms/TaggedInput';
import Fieldset from 'rzp/ui/Forms/Fieldset';
import * as NotificationActions from 'rzp/modules/notifications';
import * as ApplicationActions from 'merchant/modules/applications';

const INFO = {
  icon:
    'Your uploaded app icon will be shown to your users on Razorpay Connect screens. The icon will also be displayed in the connected applications list',
  dev:
    "End-point on your development server that we'll redirect your users back to after they connect with Razorpay. Can be localhost. If you provide a comma-separated list, we will allow redirects to any of them via the redirect_uri parameter and default to the first one.",
  prod:
    "End-point on your production server that we'll redirect your users back to after they connect with Razorpay. Must be HTTPS. If you provide a comma-separated list, we will allow redirects to any of them via the redirect_uri parameter and default to the first one.",
};

const selector = formValueSelector('newApplicationForm');

@withRouter
@connect(
  state => {
    return {
      user: state.session.user,
      applications: state.applications.items,
    };
  },
  { ...ApplicationActions, ...NotificationActions }
)
@reduxForm({
  form: 'newApplicationForm',
})
class NewApplicationForm extends Component {
  state = {
    edit: false,
    details: {},
    showDevSecret: false,
    showProdSecret: false,
  };
  componentWillMount() {
    let id = this.props.match.params.id;
    if (!id) return;
    this.setState({ edit: true });
    var appDetails = this.props.applications.filter(app => app.id === id);
    if (appDetails.length) {
      const data = appDetails[0];
      this.initForm(data);
      return;
    }
    this.props
      .fetchApplication(id)
      .then(data => {
        this.initForm(data);
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: `Application id '${id}' not found`,
        });
        this.props.history.replace('/applications');
      });
  }

  componentWillReceiveProps(nextProps) {
    this.setState({ edit: !!nextProps.match.params.id });
  }

  initForm(data) {
    this.setState({
      details: data,
    });
    this.props.initialize(data);
  }

  openPreviewPage = () => {
    // open in a popup
    var prefixPos = window.location.hostname.indexOf('-');
    var prefix =
      prefixPos !== -1
        ? 'https://' + window.location.hostname.substr(0, prefixPos)
        : 'http://';
    var hostname = prefix + 'auth.razorpay.com';
    const popupUrl =
      hostname +
      `/authorize?response_type=code&client_id=${this.state.details
        .client_details.dev.id}&redirect_uri=${this.state.details
        .website}&scope=read_only&state=current_state`;

    window.open(popupUrl, 'PopupPreview');
  };

  // save handler
  create = props => {
    return this.props
      .createApplication(props, 'logo')
      .then(application => {
        // this.setState({edit: true});
        this.initForm(application);
        this.props.history.replace(`/applications/${application.id}`);
        this.props.showNotification({
          type: 'success',
          message: 'Application created successfully',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: "Couldn't create application",
        });
      });
  };

  update = props => {
    const payload = {
      name: props.name,
      website: props.website,
      client_details: [
        {
          id: props.client_details.dev.id,
          redirect_url: props.client_details.dev.redirect_url,
        },
        {
          id: props.client_details.prod.id,
          redirect_url: props.client_details.prod.redirect_url,
        },
      ],
      file: props.file,
    };
    return this.props
      .updateApplication(this.state.details.id, payload, 'logo')
      .then(application => {
        this.props.showNotification({
          type: 'success',
          message: 'Application saved successfully',
        });
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: "Couldn't save application",
        });
      });
  };

  showDevSecret = e => {
    e.preventDefault();
    this.setState({ showDevSecret: true });
  };
  showProdSecret = e => {
    e.preventDefault();
    this.setState({ showProdSecret: true });
  };

  render() {
    const { handleSubmit } = this.props;

    return (
      <div class="content-box new-application-form">
        <div class="content-header">
          <Link to="/applications" class="breadcrumb__backNav--link ">
            <i class="icon icon-arrow-back" />
            <span> Back</span>
          </Link>
          <strong>
            {' '}/ {this.state.edit ? 'Edit' : 'Create'} Application
          </strong>
        </div>
        <form
          class="form-horizontal"
          onSubmit={handleSubmit(this.state.edit ? this.update : this.create)}
        >
          <Fieldset>
            <div class="form-group">
              <label class="col-md-2 control-label label-required">Name</label>
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
              <div class="col-md-offset-2 upload-container col-md-1">
                <div class="upload-inner" style={{ padding: '0' }}>
                  <label htmlFor="logo-upload">
                    {this.state.details.logo_url === null ||
                    typeof this.state.details.logo_url === 'undefined'
                      ? <span style={{ fontWeight: 'normal' }}>
                          <i class="fa fa-folder-open" />
                          Upload App Icon
                        </span>
                      : <img
                          class="media-object"
                          style={{ width: '100%' }}
                          src={this.state.details.logo_url}
                        />}
                    <input
                      name="file"
                      id="logo-upload"
                      type="file"
                      class="hide"
                      onChange={e => {
                        this.props.change('file', e.target.files[0]);
                      }}
                    />
                  </label>
                </div>
              </div>
              <small class="col-md-8 help-block">
                <i class="icon icon-info-circle" />
                <span>
                  {INFO.icon}
                </span>
              </small>
            </div>

            {this.state.edit &&
              <div class="edit-details">
                <div class="col-md-offset-2 col-md-10">
                  <h5 class="form-header text-left">Development</h5>
                </div>

                <div class="form-group">
                  <label class="col-md-2 control-label">Client ID</label>
                  <div class="col-md-4">
                    <Field
                      name="client_details.dev.id"
                      component={InputField}
                      disabled={true}
                      class="form-control copy-field"
                      placeholder="Client ID"
                    />
                  </div>
                  <label class="col-md-2 control-label">Client Secret</label>
                  <div class="col-md-4">
                    <Field
                      name="client_details.dev.secret"
                      disabled={true}
                      type={this.state.showDevSecret ? 'text' : 'password'}
                      component={InputField}
                      class="form-control copy-field"
                      placeholder="Client Secret"
                    />
                    {!this.state.showDevSecret &&
                      <button
                        class="btn btn-default btn-show-secret"
                        onClick={this.showDevSecret}
                      >
                        <i class="fa fa-eye" />
                      </button>}
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-2 control-label">Redirect URIs</label>
                  <div class="col-md-10">
                    <Field
                      name="client_details.dev.redirect_url"
                      component={TaggedInput}
                      class="form-control tagged-input"
                      placeholder="http://test-app.com/"
                      validator={isUrl}
                    />
                  </div>
                  <div class="clearfix" />
                  <small class="col-md-offset-2 col-md-10 help-block">
                    <i class="icon icon-info-circle" />
                    <span>
                      {INFO.dev}
                    </span>
                  </small>
                </div>

                <div class="col-md-offset-2 col-md-10">
                  <h5 class="form-header text-left">Production</h5>
                </div>

                <div class="form-group">
                  <label class="col-md-2 control-label">Client ID</label>
                  <div class="col-md-4">
                    <Field
                      name="client_details.prod.id"
                      component={InputField}
                      disabled={true}
                      class="form-control copy-field"
                      placeholder="Client ID"
                    />
                  </div>
                  <label class="col-md-2 control-label">Client Secret</label>
                  <div class="col-md-4">
                    <Field
                      name="client_details.prod.secret"
                      disabled={true}
                      component={InputField}
                      type={this.state.showProdSecret ? 'text' : 'password'}
                      class="form-control copy-field"
                      placeholder="Client Secret"
                    />
                    {!this.state.showProdSecret &&
                      <button
                        class="btn btn-default btn-show-secret"
                        onClick={this.showProdSecret}
                      >
                        <i class="fa fa-eye" />
                      </button>}
                  </div>
                </div>

                <div class="form-group">
                  <label class="col-md-2 control-label">Redirect URIs</label>
                  <div class="col-md-10">
                    <Field
                      name="client_details.prod.redirect_url"
                      component={TaggedInput}
                      class="form-control tagged-input"
                      placeholder="http://test-app.com/"
                      validator={isUrl}
                    />
                  </div>
                  <div class="clearfix" />
                  <small class="col-md-offset-2 col-md-10 help-block">
                    <i class="icon icon-info-circle" />
                    <span>
                      {INFO.prod}
                    </span>
                  </small>
                </div>
              </div>}

            <div class="form-group">
              <div class="col-md-offset-3 col-md-9">
                <div class="btn-toolbar">
                  <AsyncButton
                    class="btn btn-primary pull-right"
                    text="Save"
                    pendingText="Saving..."
                    onClick={handleSubmit(
                      this.state.edit ? this.update : this.create
                    )}
                  />

                  {this.state.edit &&
                    <AsyncButton
                      type="button"
                      class="btn btn-default pull-right"
                      text="Preview OAuth Page"
                      onClick={this.openPreviewPage}
                    />}
                </div>
              </div>
            </div>
          </Fieldset>
        </form>
      </div>
    );
  }
}

export default NewApplicationForm;
