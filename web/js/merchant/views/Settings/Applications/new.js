import { Component, Fragment } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { Link, NavLink } from 'react-router-dom';
import { withRouter } from 'common/deprecated/withRouter';
import { ArrowLeftIcon, Text } from '@razorpay/blade/components';
import { autoPrefixUrls, titleCase, isProductionEnv } from 'common/utils/rzp-utils';
import { withSplitzService } from 'common/splitz';

import { required, lenientUrl, flexibleDevUrl } from 'common/utils/validators';

import * as NotificationActions from 'merchant_common/reducers/notifications';
import * as ApplicationActions from 'merchant/reducers/applications';
import { openModal, closeModal } from 'merchant_common/reducers/modals';

import InputField from 'common/ui/Forms/InputField';
import TaggedInput from 'common/ui/Forms/TaggedInput';
import Fieldset from 'common/ui/Forms/Fieldset';
import LoaderDots from 'common/ui/LoaderDots';

import AppWebhook from './AppWebhook';

import {
  StyledHeader,
  StyledLink,
} from 'merchant/views/PartnerDashboard/Settings/configuration/styles';
import { compose } from 'redux';

const info = (user) => ({
  icon: (
    <span>
      Your uploaded app icon will be shown to your users on {user.isOrgRZP ? 'Razorpay' : 'the'}{' '}
      Connect screens. The icon will also be displayed in the connected applications list.
      <br /> Maximum image size: <b>1 MB</b>. You can reduce the size on{' '}
      {user.isOrgAllowedFunctionality('external_links') ? (
        <a href="https://tinypng.com" target="_blank" rel="noreferrer noopener">
          tinypng.com.
        </a>
      ) : (
        'tinypng.com'
      )}
    </span>
  ),
  dev: `Add comma separated URIs. URI can be localhost. We'll redirect your users back to any of the URI provided, after they connect ${
    user.isOrgRZP ? 'with Razorpay' : 'their account'
  }.`,
  prod: (
    <span>
      Add comma separated URIs. <b>URIs must be HTTPs.</b> We'll redirect your users back to any of
      the URI provided, after they connect {user.isOrgRZP ? 'with Razorpay' : 'their account'}.
    </span>
  ),
});

function readURL(input, self) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();

    reader.onload = (e) => {
      self.setState({
        details: {
          ...self.state.details,
          logo_url: e.target.result,
        },
      });
    };

    reader.readAsDataURL(input.files[0]);
  }
}

const LogoSizeErrorMsg = 'Please upload an image under the size of 1 MB';

function isLogoSizeError(err) {
  return (
    err?.errors?.length > 0 &&
    err?.errors[0] === 'Size of the logo is too big. Upload a smaller file size.'
  );
}

class NewApplicationForm extends Component {
  state = {
    edit: false,
    details: {},
  };

  componentDidMount() {
    const id = this.props.match.params.id;
    if (!id) return;
    this.setState({ edit: true });

    if (this.props.user.isPartner('pure_platform')) {
      // since partner users need not be activated
      this.fetchWebhooks('live');
      this.fetchWebhooks('test');
    } else {
      this.fetchWebhooks();
    }

    const appDetails = this.props.applications.filter((app) => app.id === id);
    if (appDetails.length) {
      const data = appDetails[0];
      this.initForm(data);
      return;
    }
    this.props
      .fetchApplication(id)
      .then((data) => {
        this.initForm(data);
      })
      .catch(() => {
        this.props.showNotification({
          type: 'error',
          message: `Application id '${id}' not found`,
        });
        this.props.history.replace('/applications');
      });
  }

  setWebhookState =
    (mode) =>
    ({ webhookLoading, webhook }) => {
      mode = mode || '';
      this.setState({
        [`${mode}webhookLoading`]: webhookLoading,
        [`${mode}webhook`]: webhook,
      });
    };

  fetchWebhooks(mode) {
    const changeWebhookState = this.setWebhookState(mode);
    changeWebhookState({ webhookLoading: false });
    // Fetch call getting app's webhook
    const appId = this.props.match.params.id;
    ApplicationActions.fetchAppWebhooks(appId, mode)
      .then((data) => {
        const webhook = data.data.items.length ? data.data.items[0] : null;
        changeWebhookState({ webhookLoading: false, webhook });
      })
      .catch(() => {
        changeWebhookState({ webhookLoading: false, webhook: null });
      });
  }

  componentDidUpdate(prevProps) {
    if (prevProps.match.params.id !== this.props.match.params.id) {
      this.setState({ edit: !!this.props.match.params.id });
    }
  }

  initForm(data) {
    this.setState({
      details: data,
    });
    this.props.initialize(data);
  }

  openPreviewPage = () => {
    // open in a popup
    const prefixPos = window.location.hostname.indexOf('-');
    const prefix =
      prefixPos !== -1
        ? `https://${window.location.hostname.substr(0, prefixPos + 1)}`
        : 'https://';
    const isProdEnv = isProductionEnv();
    const hostname = isProdEnv ? `${prefix}auth.razorpay.com` : `${prefix}auth.dev.razorpay.in`;
    const clientId = this.state.details.client_details[isProdEnv ? 'prod' : 'dev'].id;
    const redirectUrl =
      this.state.details.client_details[isProdEnv ? 'prod' : 'dev'].redirect_url[0] ?? 'localhost';
    const popupUrl = `${hostname}/authorize?response_type=code&client_id=${clientId}&redirect_uri=${encodeURIComponent(
      redirectUrl,
    )}&scope=read_only&state=current_state`;
    window.open(popupUrl, 'PopupPreview');
  };

  // save handler
  create = (props) => {
    const data = { ...props };

    if (data.website) {
      data.website = autoPrefixUrls(data.website);
    }

    return this.props
      .createApplication(data, 'logo')
      .then((application) => {
        this.initForm(application);
        this.props.history.replace(
          `${this.props.location.pathname.replace('new', '')}${application.id}`,
        );
        this.props.showNotification({
          type: 'success',
          message: 'Application created successfully',
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: isLogoSizeError(err) ? LogoSizeErrorMsg : err.errors,
        });
      });
  };

  handleBlurOnProdURI = (val, isValid) => {
    if (!isValid) {
      this.props.showNotification({
        type: 'error',
        message: 'Production Redirect URI must be HTTPS',
      });
    }
  };

  // Currently only being for development URIs
  prependHTTPinUrl(urlList) {
    return urlList.map((url) => autoPrefixUrls(url));
  }

  update = (props) => {
    const data = { ...props };

    if (data.website) {
      data.website = autoPrefixUrls(data.website);
    }

    if (data.client_details.dev.redirect_url) {
      data.client_details.dev.redirect_url = this.prependHTTPinUrl(
        data.client_details.dev.redirect_url,
      );
    }

    const payload = {
      name: data.name,
      website: data.website,
      client_details: [
        {
          id: data.client_details.dev.id,
          redirect_url: data.client_details.dev.redirect_url,
        },
        {
          id: data.client_details.prod.id,
          redirect_url: data.client_details.prod.redirect_url,
        },
      ],
    };

    if (data.file) {
      payload.file = data.file;
    }
    return this.props
      .updateApplication(this.state.details.id, payload, 'logo')
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Application saved successfully',
        });
      })
      .catch((err) => {
        this.props.showNotification({
          type: 'error',
          message: isLogoSizeError(err) ? LogoSizeErrorMsg : "Couldn't save application",
        });
      });
  };

  showDevSecret = (e) => {
    e.preventDefault();
    this.setState({ showDevSecret: true });
  };
  showProdSecret = (e) => {
    e.preventDefault();
    this.setState({ showProdSecret: true });
  };

  onWebhookSave = (mode) => (webhook) => {
    const changeWebhookState = this.setWebhookState(mode);
    this.props.closeModal();
    changeWebhookState({ webhook: webhook.data });
  };

  showWebhookModal =
    (mode = '') =>
    () => {
      this.props.openModal({
        component: (
          <AppWebhook
            webhook={this.state[`${mode}webhook`]}
            loading={this.state[`${mode}webhookLoading`]}
            appId={this.props.match.params.id}
            onSave={this.onWebhookSave(mode)}
            mode={mode}
            isApplication
          />
        ),
      });
    };

  render() {
    const {
      handleSubmit,
      location: { pathname },
      match: { params },
      user,
      splitz,
    } = this.props;
    const { abExperiments } = splitz;
    const { edit, details } = this.state;
    const isPartner = pathname.includes('/partners');
    const isExpEnabledForConfigurator =
      abExperiments.partnerships_oauth_phantom_configurator?.variables.result === 'on';

    return (
      <div className="content-box new-application-form">
        {edit && isPartner ? (
          <>
            <header>
              <StyledHeader>
                <StyledLink to="/partners/applications">
                  <ArrowLeftIcon
                    color="interactive.icon.primary.subtle"
                    size="medium"
                    marginRight="5px"
                  />
                  All Applications
                </StyledLink>
                <strong>
                  &nbsp;/ {details?.name}&nbsp;({details?.id})
                </strong>
              </StyledHeader>
            </header>
            <header>
              <NavLink to={`/partners/applications/${params.id}`}>Integration Settings</NavLink>
              {isExpEnabledForConfigurator ? (
                <NavLink
                  to={`/partners/applications/configuration/${params.id}`}
                  state={{ appName: details?.name }}
                >
                  Onboarding UI Configurator
                </NavLink>
              ) : null}
            </header>
          </>
        ) : (
          <header>
            <StyledHeader>
              <Link
                to={pathname.substring(0, pathname.lastIndexOf('/'))}
                className="breadcrumb__backNav--link "
              >
                <i className="i i-arrow-back" />
                <span> Back&nbsp;</span>
              </Link>
              <Text weight="semibold" color="action.tertiary.primary.default">
                {' '}
                /&nbsp; {edit ? 'Edit' : 'Create'} Application
              </Text>
            </StyledHeader>
          </header>
        )}
        <form className="form-horizontal" onSubmit={handleSubmit(edit ? this.update : this.create)}>
          <Fieldset>
            <div className="form-group">
              <label className="col-md-2 control-label label-required">Name</label>
              <div className="col-md-10">
                <Field
                  name="name"
                  component={InputField}
                  className="form-control"
                  placeholder="Test App"
                  validate={[required()]}
                />
              </div>
            </div>
            <div className="form-group">
              <label className="col-md-2 control-label label-required">Website</label>
              <div className="col-md-10">
                <Field
                  name="website"
                  component={InputField}
                  className="form-control"
                  placeholder="http://test-app.com/"
                  validate={[required(), lenientUrl('Please enter a valid URL')]}
                />
              </div>
            </div>

            <div className="form-group">
              <div className="col-md-offset-2 upload-container col-md-1">
                <div className="upload-inner">
                  <label htmlFor="logo-upload">
                    {this.state.details.logo_url === null ||
                    typeof this.state.details.logo_url === 'undefined' ? (
                      <span className="upload-icon" style={{ fontWeight: 'normal' }}>
                        <i className="fa fa-folder-open" />
                        Upload App Icon
                      </span>
                    ) : (
                      <img
                        className="media-object"
                        id="logo-url"
                        style={{ width: '100%' }}
                        src={this.state.details.logo_url}
                      />
                    )}
                    <input
                      name="file"
                      id="logo-upload"
                      type="file"
                      className="hide"
                      onChange={(e) => {
                        this.props.change('file', e.target.files[0]);
                        readURL(e.target, this);
                      }}
                    />
                  </label>
                </div>
              </div>
              <small className="col-md-8 help-block">
                <i className="i i-info-circle" />
                <span>{info(user).icon}</span>
              </small>
            </div>

            {this.state.edit && (
              <div className="edit-details">
                <div className="section-divide" />
                <AppDetails type="dev" user={user} />
                <div className="section-divide" />
                <AppDetails type="prod" user={user} />
              </div>
            )}

            {this.state.edit && (
              <div className="form-group">
                <label className="col-md-2 control-label">Webhooks:</label>
                {user.isPartner('pure_platform') ? (
                  ['live', 'test'].map((mode, idx) => {
                    const webhook = this.state[`${mode}webhook`];
                    const webhookLoading = this.state[`${mode}webhookLoading`];
                    return (
                      <div className="col-md-5" key={idx}>
                        <WebhookDetail
                          webhook={webhook}
                          webhookLoading={webhookLoading}
                          mode={mode}
                          key={mode}
                          showWebhookModal={this.showWebhookModal(mode)}
                        />
                      </div>
                    );
                  })
                ) : (
                  <div className="col-md-10">
                    <WebhookDetail
                      webhook={this.state.webhook}
                      webhookLoading={this.state.webhookLoading}
                      showWebhookModal={this.showWebhookModal()}
                    />
                  </div>
                )}
              </div>
            )}

            <div className="section-divide" />
            <div className="form-group">
              <div className="col-md-offset-3 col-md-9">
                <div className="btn-toolbar">
                  <AsyncButton
                    className="btn btn-primary pull-right"
                    text="Save"
                    type="submit"
                    pendingText="Saving..."
                    onClick={handleSubmit(this.state.edit ? this.update : this.create)}
                  />

                  {this.state.edit && (
                    <AsyncButton
                      type="button"
                      className="btn btn-default pull-right m-r"
                      text="Preview OAuth Page"
                      onClick={this.openPreviewPage}
                    />
                  )}
                </div>
              </div>
            </div>
          </Fieldset>
        </form>
      </div>
    );
  }
}

class AppDetails extends Component {
  state = { showSecret: false };

  showSecret = () => {
    this.setState({ showSecret: true });
  };

  render() {
    const { type, user } = this.props;
    return (
      <>
        <div className="col-md-offset-2 col-md-10">
          <h4 className="form-header text-left">
            {type === 'dev' ? 'Development' : 'Production'}:
          </h4>
        </div>

        <div className="form-group">
          <label className="col-md-2 control-label">Client ID</label>
          <div className="col-md-4">
            <Field
              name={`client_details.${type}.id`}
              component={InputField}
              disabled={true}
              className="form-control copy-field"
              placeholder="Client ID"
            />
          </div>
          <label className="col-md-2 control-label">Client Secret</label>
          <div className="col-md-4">
            <Field
              name={`client_details.${type}.secret`}
              disabled={true}
              type={this.state.showSecret ? 'text' : 'password'}
              component={InputField}
              className="form-control copy-field"
              placeholder="Client Secret"
            />
            {!this.state.showSecret && (
              <button className="btn btn-default btn-show-secret" onClick={this.showSecret}>
                <i className="fa fa-eye" />
              </button>
            )}
          </div>
        </div>

        <div className="form-group">
          <label className="col-md-2 control-label">Redirect URIs</label>
          <div className="col-md-10">
            <Field
              name={`client_details.${type}.redirect_url`}
              component={TaggedInput}
              className="form-control tagged-input"
              placeholder="http://test-app.com/"
              validator={flexibleDevUrl}
            />
          </div>
          <div className="clearfix" />
          <small className="col-md-offset-2 col-md-10 help-block">
            <i className="i i-info-circle" />
            <span>{info(user)[type]}</span>
          </small>
        </div>
      </>
    );
  }
}

function WebhookDetail({ webhookLoading, webhook, mode = '', showWebhookModal }) {
  return webhookLoading ? (
    <LoaderDots customClass="loader-dots" />
  ) : (
    <div>
      {webhook ? (
        <Fragment>
          <div>
            <strong>Url: </strong> {webhook.url}
          </div>
          <div>
            <strong>Active: </strong>{' '}
            <i
              className={`fa ${webhook.active ? 'fa-check text-success' : 'fa-close text-danger'}`}
            />
          </div>
          <div>
            <strong>Total Events: </strong>
            {Object.keys(webhook.events).filter((i) => !!webhook.events[i]).length}
          </div>
        </Fragment>
      ) : (
        <p>No {mode} webhook created</p>
      )}
      <button className="btn btn-default webhook-btn m-t" onClick={showWebhookModal} type="button">
        Manage {mode && titleCase(mode)} Webhook
      </button>
    </div>
  );
}

export default compose(
  withSplitzService,
  withRouter,
  connect(
    (state) => {
      return {
        user: state.session.user,
        applications: state.applications.items,
      };
    },
    { ...ApplicationActions, ...NotificationActions, openModal, closeModal },
  ),
  reduxForm({
    form: 'newApplicationForm',
  }),
)(NewApplicationForm);
