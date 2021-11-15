import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import FileUploadButton from 'common/ui/FileUpload/Button';
import {
  uploadLogo,
  fetchLocale,
  updateLocale,
  saveLocale,
  removeLogo,
} from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import { getIcon } from './components/paymentMethodIcons';
import SwitchField from 'common/ui/Forms/SwitchField';
import * as ModalActions from 'merchant_common/reducers/modals';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import LoaderDots from 'common/ui/LoaderDots';
import IntoView from 'common/ui/IntoView';
import { CHECKOUT_LANG } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import Button from 'common/new-ui/Button';
import { getCustomURL } from 'merchant/components/DocsLink';
import EasterEgg from 'merchant/components/EasterEgg';

const languageOptions = [
  { name: 'English', code: 'en' },
  { name: 'Bengali', code: 'ben' },
  { name: 'Hindi', code: 'hi' },
  { name: 'Marathi', code: 'mar' },
  { name: 'Gujarati', code: 'guj' },
  { name: 'Tamil', code: 'tam' },
  { name: 'Telugu', code: 'tel' },
];

class CheckoutTheme extends Component {
  state = { brandColor: this.props.config.brand_color };

  componentWillMount() {
    this.props.initialize(this.props.config);
    this.props.fetchLocale();

    const script = document.createElement('script');

    script.onload = () => {
      this.updatePreviewTextClr();
    };

    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }

  updatePreviewTextClr() {
    this.setState((prevState) => {
      const textClr =
        !window.colorLib || window.colorLib.isDark(prevState.brandColor)
          ? '#fff'
          : 'rgba(0, 0, 0, 0.85)';

      const colorVariations = window.colorLib.getColorVariations(prevState.brandColor);

      return {
        textClr,
        colorVariations,
      };
    });
  }

  uploadLogo = (event) => {
    analyticsTrack({
      objectName: 'logo choose file',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
    let file;
    if (event?.target?.files && event?.target?.files.length > 0) {
      file = event.target.files[0];
    }
    return this.props
      .uploadLogo(file, 'logo')
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'File Uploaded Successfully',
        });
        analyticsTrack({
          objectName: 'logo choose file',
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Success',
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
        analyticsTrack({
          objectName: 'logo choose file',
          actionName: 'result',
          screen: 'settings',
          properties: {
            location: 'configuration',
            status: 'Failure',
            failureReason: errors[0],
            ...getCommonAnalyticsProperties(window.rzp_user),
          },
        });
      });
  };
  removeLogo = () => {
    const config = this.props.config;
    const payLoad = { ...config, logo_url: null };
    return this.props
      .removeLogo(payLoad)
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'Logo Removed Successfully',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };
  onSave = (e) => {
    this.analytics();
    this.props.handleSubmit(this.props.onSave)(e, 'theme');
  };

  analytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Checkout Theme',
    });
    analyticsTrack({
      objectName: 'theme color save changes',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        colorCode: this.state.brandColor,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  onChangeBrandColor = (e) => {
    analyticsTrack({
      objectName: 'theme color',
      actionName: 'switched',
      screen: 'settings',
      properties: {
        location: 'configuration',
        previousColorCode: this.state.brandColor,
        newColorCode: e.target.value,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    this.setState({ brandColor: e.target.value });

    this.updatePreviewTextClr();
  };

  onChangeLocale = (e) => {
    this.props.updateLocale(e.target.value);
  };

  saveLocale = (e) => {
    e.preventDefault();
    const data = {
      type: 'locale',
      config: this.props.locale.config,
    };
    if (this.props.locale.id) {
      data.id = this.props.locale.id;
    } else {
      data.name = '_';
      data.is_default = true;
    }

    return this.props.saveLocale(data).then(() => {
      this.props.showNotification({
        type: 'success',
        message: 'Default language updated',
      });
    });
  };

  onClickKnowMore = () => {
    this.props.openModal({
      size: 'medium',
      component: <CovidKnowMore isCovidDonations />,
    });
  };

  render() {
    const { textClr, colorVariations } = this.state;
    const { user } = this.props;
    const isEnabled = user.isFeatureEnabled('covid_19_relief');
    return (
      <div class="panel panel-default panel-theme">
        <div class="panel-section--theme">
          <div class="panel-heading">
            <span class="title">Account Settings</span>
          </div>
          <div class="panel-body">
            <form class="form-horizontal">
              <div class="form-group theme-select">
                {user.isCovidReliefFlowEnabled &&
                  user.business_type !== 7 &&
                  user.business_type !== 9 && (
                    <div class="covid-donations__settings">
                      {this.props.isLoading ? (
                        <LoaderDots />
                      ) : (
                        <div
                          class={
                            user.isFeatureEnabled('covid_19_relief') ? 'text-primary' : 'text-faded'
                          }
                        >
                          <i class="i i-Donate" />
                          <strong>
                            Donations{' '}
                            {user.isFeatureEnabled('covid_19_relief') ? `enabled` : `disabled`} on
                            Checkout
                          </strong>
                          <span class="toggler-btn">
                            <SwitchField
                              defaultChecked={!!isEnabled}
                              onChange={this.props.onSwitchChange}
                              type="prime"
                            />
                            {user.isFeatureEnabled('covid_19_relief') ? (
                              <b class="text-primary">Enabled</b>
                            ) : (
                              <b class="text-faded">Disabled</b>
                            )}
                          </span>
                        </div>
                      )}
                      <br />
                      <div>
                        {' '}
                        <p style={{ marginBottom: '8px' }}>
                          Customers will have the option to donate for COVID Relief on the checkout
                          page post succesful payment.{' '}
                          <strong
                            style={{ cursor: 'pointer', color: '#528FF0' }}
                            onClick={this.onClickKnowMore}
                          >
                            Know More
                          </strong>
                        </p>
                      </div>
                    </div>
                  )}
                <label class="col-md-12 col-sm-12" style={{ marginTop: 12 }}>
                  <strong>Theme Color</strong>
                </label>
                <div class="col-md-5 col-sm-6" style={{ position: 'relative' }}>
                  <div class="color-picker">
                    <Field
                      name="brand_color"
                      component="input"
                      class="form-control"
                      type="color"
                      onChange={this.onChangeBrandColor}
                    />
                  </div>
                  <Field
                    name="brand_color"
                    component="input"
                    class="form-control"
                    onChange={this.onChangeBrandColor}
                  />
                </div>
                <div class="col-md-3 col-sm-6">
                  <AsyncButton
                    class="btn btn-primary"
                    text="Save"
                    pendingText="Saving..."
                    onClick={this.onSave}
                  />
                </div>
                <div class="col-md-12 col-md-6 description">
                  Choose a theme color for your brand.
                  <br />
                  The default theme color will be used if none is specified.
                </div>
              </div>

              <div class="form-group">
                <label class="col-md-12" style={{ marginTop: 12 }}>
                  <strong>Your Logo</strong>
                </label>
                <div class="col-md-12 media" style={{ marginTop: 0 }}>
                  {this.props.config.logo_url && (
                    <div class="media-left">
                      <a>
                        <img
                          class="media-object"
                          src={this.props.config.logo_url}
                          width="72"
                          height="72"
                        />
                      </a>
                    </div>
                  )}

                  <div class="media-body">
                    <FileUploadButton
                      text={this.props?.config?.logo_url ? 'Change Logo' : 'Choose File'}
                      labelClass="btn-primary"
                      accept="image/jpeg,image/jpg,image/png"
                      maxSize="1048576"
                      onChange={this.uploadLogo}
                    />
                    {this.props?.config?.logo_url && (
                      <span className="remove-logo">
                        <Button.Transparent type="button" onClick={this.removeLogo}>
                          Remove
                        </Button.Transparent>
                      </span>
                    )}

                    <div class="help-block" style={{ marginBottom: 0 }}>
                      <i style={{ fontSize: 12 }}>Max file size: 1MB</i>
                    </div>
                  </div>
                  <div class="description">
                    Choose a square image of minimum dimensions 256x256 px.
                  </div>
                </div>
              </div>
              <IntoView hashedWith={CHECKOUT_LANG}>
                {this.props.locale && (
                  <div class="form-group">
                    <label class="col-md-12" style={{ marginTop: 12 }}>
                      <strong>
                        <TextHighlighter hashedWith={CHECKOUT_LANG}>
                          Default Language
                        </TextHighlighter>
                      </strong>
                    </label>
                    <div class="col-md-6" style={{ marginTop: 0 }}>
                      <select
                        class="form-control"
                        defaultValue={this.props.locale.config.language_code}
                        onChange={this.onChangeLocale}
                      >
                        {languageOptions.map((l) => (
                          <option key={l.code} value={l.code}>
                            {l.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div class="col-md-3 col-sm-6">
                      <AsyncButton
                        class="btn btn-primary"
                        text="Save"
                        pendingText="Saving..."
                        onClick={this.saveLocale}
                      />
                    </div>
                    <div class="col-md-12">
                      <br />
                      Default language will be used on the Checkout page if customer doesn’t specify
                      a language.
                    </div>
                  </div>
                )}
              </IntoView>
            </form>
            <div class="footer-note">
              Changes will reflect on{' '}
              <ShowWhen
                additionalCondition={() => user.isOrgAllowedFunctionality('external_links')}
              >
                <a
                  target="_blank"
                  rel="noopener noreferrer"
                  href={getCustomURL('https://razorpay.com/payment-gateway/')}
                >
                  Checkout page
                </a>
                ,{' '}
                <a
                  target="_blank"
                  rel="noopener noreferrer"
                  href={getCustomURL('https://razorpay.com/payment-links/')}
                >
                  Payment Links
                </a>
                ,{' '}
                <a
                  target="_blank"
                  rel="noopener noreferrer"
                  href={getCustomURL('https://razorpay.com/invoices/')}
                >
                  Invoices
                </a>{' '}
                &{' '}
                <a
                  target="_blank"
                  rel="noopener noreferrer"
                  href={getCustomURL('https://razorpay.com/payment-pages')}
                >
                  Payment pages
                </a>
                .
              </ShowWhen>
            </div>
          </div>
        </div>
        <div class="panel-section--checkout">
          <div id="preview-label">Preview</div>
          <div id="preview-checkout">
            <div id="checkout-header" style={{ backgroundColor: this.state.brandColor }}>
              {this.props.config.logo_url && (
                <div id="header-logo">
                  <img src={this.props.config.logo_url} width="100%" />
                </div>
              )}

              <div id="header-details">
                {textClr && (
                  <div id="merchant" style={{ color: textClr }}>
                    <div id="merchant-name">{this.props.user.contact_name}</div>
                    <div id="merchant-desc">Order ID</div>
                    <div id="amount">₹1</div>
                  </div>
                )}
              </div>
            </div>
            <div id="preview-checkout-form">
              <img src="/img/preview-checkout-form.png" width="100%" />
              <div id="payment-method-icons">
                {['card', 'netbanking', 'wallet', 'upi', 'emi', 'qr'].map((type, ix) => (
                  <span key={ix}>
                    {getIcon(type, colorVariations)}
                    <span class="payment-method-label">{type}</span>
                  </span>
                ))}
              </div>
            </div>
          </div>
          <EasterEgg extraClass="ftx-settings-page" page="Settings" />
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ ...state.config, user: state.session.user }), {
    uploadLogo,
    removeLogo,
    showNotification,
    fetchLocale,
    updateLocale,
    saveLocale,
    ...ModalActions,
  }),
  reduxForm({}),
)(CheckoutTheme);
