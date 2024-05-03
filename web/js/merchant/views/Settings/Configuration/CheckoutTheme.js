import { Component } from 'react';
import { FolderIcon, Button as BladeButton, Text, Box } from '@razorpay/blade/components';
import EmailHiddenPreviewImage from 'assets/checkout/preview-checkout-form-email-hidden.png';
import EmailOptionalPreviewImage from 'assets/checkout/preview-checkout-form-email-optional.png';
import EmailRequiredPreviewImage from 'assets/checkout/preview-checkout-form.png';
import PropTypes from 'prop-types';
import AsyncButton from 'react-async-button';
import { connect } from 'react-redux';
import { compose } from 'redux';
import { Field, reduxForm } from 'redux-form';

import { withI18Service } from 'common/i18';
import Button from 'common/new-ui/Button';
import { getCurrencySymbol } from 'common/ui/Amount';
import CovidKnowMore from 'common/ui/CovidKnowMore';
import FileUploadButton from 'common/ui/FileUpload/Button';
import SwitchField from 'common/ui/Forms/SwitchField';
import IntoView from 'common/ui/IntoView';
import LoaderDots from 'common/ui/LoaderDots';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties, camelize } from 'common/utils/rzp-utils';
import { getCustomURL } from 'merchant/components/DocsLink';
import EasterEgg from 'merchant/components/EasterEgg';
import FileUpload from 'merchant/components/File/Upload';
import {
  uploadLogo,
  fetchLocale,
  updateLocale,
  updateFeatures,
  updateEmailConfig,
  saveLocale,
  removeLogo,
  getEmailConfigFlags,
  EmailLessCheckoutConfigOptions,
  CHECKOUT_EMAIL_FEATURE_FLAG,
} from 'merchant/reducers/config';
import ImageCropperModal from 'merchant/views/Settings/Configuration/ImageCropperModal';
import {
  THUMBNAIL_SIZE_LIMIT,
  FILE_TYPES,
  UPLOAD_IMAGE_HERE,
  LOGO_REMOVED_SUCCESSFULLY,
  SUCCESS,
  ERROR,
} from 'merchant/views/Settings/Configuration/constants';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import { getIcon } from './components/paymentMethodIcons';
import * as ModalActions from 'merchant_common/reducers/modals';

import { ACCOUNT_SETTINGS, CHECKOUT_LANG, CHECKOUT_EMAIL_SETTINGS } from './deeplink-constants';
import TextHighlighter from 'common/ui/TextHighlighter';
import { selfServeTrackInitiate, selfServeTrackSuccess } from 'common/utils/selfServeAnalytics';
import { BrandName } from 'merchant/views/Account/Profile/components/BrandName';

const languageOptions = [
  { name: 'English', code: 'en' },
  { name: 'Bengali', code: 'ben' },
  { name: 'Hindi', code: 'hi' },
  { name: 'Marathi', code: 'mar' },
  { name: 'Gujarati', code: 'guj' },
  { name: 'Tamil', code: 'tam' },
  { name: 'Telugu', code: 'tel' },
];

const checkoutEmailConfigOptions = [
  {
    name: 'No (Default)',
    code: EmailLessCheckoutConfigOptions.NO,
  },
  {
    name: 'As an optional field',
    code: EmailLessCheckoutConfigOptions.OPTIONAL,
  },
  {
    name: 'As a mandatory field',
    code: EmailLessCheckoutConfigOptions.REQUIRED,
  },
];

// eslint-disable-next-line react/no-unsafe
class CheckoutTheme extends Component {
  state = {
    brandColor: this.props.config.brand_color,
    rectangularImageFile: null,
    isLoading: false,
  };

  static contextTypes = {
    confirm: PropTypes.func,
  };

  UNSAFE_componentWillMount() {
    this.props.fetchLocale();

    const script = document.createElement('script');

    script.onload = () => {
      this.updatePreviewTextClr();
    };

    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }

  componentDidMount() {
    this.updateCheckoutClr();
  }

  componentDidUpdate(prevProps) {
    if (this.props.config.brand_color !== prevProps.config.brand_color) {
      this.updateCheckoutClr();
    }
  }

  updateCheckoutClr() {
    const brand_color = this.props.config.brand_color
      ? this.props.config.brand_color
      : this.props.org?.merchant_styles?.checkout_theme_color;

    this.props.initialize({
      ...this.props.config,
      brand_color: brand_color || '#528FF0',
    });

    this.setState({ brandColor: brand_color });
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
    selfServeTrackInitiate({
      selfServeAction: 'Brand Logo Uploaded',
      page: 'Config',
      screen: 'Settings',
    });
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
        selfServeTrackSuccess({
          selfServeAction: 'Brand Logo Uploaded',
          page: 'Config',
          screen: 'Settings',
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
    selfServeTrackInitiate({
      selfServeAction: 'Theme Color Changed',
      page: 'Config',
      screen: 'Settings',
    });
    window.rzpAnalytics?.({
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

  onChangeEmailConfig = (e) => {
    this.props.updateEmailConfig(e.target.value);
  };

  analyticsForFeatureChange = (featureName, isFeatureEnabled, optionalProperties = {}) => {
    const analyticsLabel = camelize(featureName);
    const analyticsObjName = featureName.toLowerCase().replace(/_/g, '-');

    analyticsTrack({
      objectName: `${analyticsObjName} toggle`,
      actionName: 'result',
      screen: 'settings',
      properties: {
        location: 'configuration',
        [analyticsLabel]: isFeatureEnabled ? 'Enabled' : 'Disabled',
        ...optionalProperties,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });
  };

  getEmailConfigUpdateRequestPayload = () => {
    const existingEmailConfigFlag = getEmailConfigFlags(this.props.features);
    const emailConfig = this.props.email_config;
    let emailOptional = false;
    let emailShown = false;
    switch (emailConfig) {
      case EmailLessCheckoutConfigOptions.OPTIONAL:
        emailOptional = true;
        emailShown = true;
        break;
      case EmailLessCheckoutConfigOptions.REQUIRED:
        emailOptional = false;
        emailShown = true;
        break;
      default:
        break;
    }
    const data = {
      features: {},
      should_sync: 0,
    };
    // add only if feature flags are changing from existing
    if (existingEmailConfigFlag.emailShown !== emailShown) {
      data.features[CHECKOUT_EMAIL_FEATURE_FLAG.SHOW_EMAIL_ON_CHECKOUT] = emailShown;
    }
    if (existingEmailConfigFlag.emailOptional !== emailOptional) {
      data.features[CHECKOUT_EMAIL_FEATURE_FLAG.EMAIL_OPTIONAL_ON_CHECKOUT] = emailOptional;
    }
    return { data, emailShown, emailOptional };
  };

  updateEmailConfig = async ({ emailOptional, emailShown, requestData }) => {
    // Used for analytics
    const updatedFeatureFlagKeyValue = {
      [CHECKOUT_EMAIL_FEATURE_FLAG.EMAIL_OPTIONAL_ON_CHECKOUT]: emailOptional,
      [CHECKOUT_EMAIL_FEATURE_FLAG.SHOW_EMAIL_ON_CHECKOUT]: emailShown,
    };

    selfServeTrackInitiate({
      selfServeAction: 'Email Config Changed',
      page: 'Config',
      screen: 'Settings',
    });

    analyticsTrack({
      objectName: 'save-email-address-preference',
      actionName: 'clicked',
      screen: 'settings',
      properties: {
        location: 'configuration',
        option_selected: this.props.email_config,
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
      toLumberjack: true,
    });

    try {
      const response = await this.props.updateFeatures(requestData, this.props.user.current);
      if (response) {
        this.props.showNotification({
          type: 'success',
          message: 'Configuration updated',
        });

        Object.entries(updatedFeatureFlagKeyValue).forEach(([key, value]) => {
          this.analyticsForFeatureChange(key, value, {
            status: 'Success',
          });
        });
      }
    } catch (err) {
      this.props.showNotification({
        type: 'error',
        message: err.errors,
      });
      Object.entries(updatedFeatureFlagKeyValue).forEach(([key, value]) => {
        this.analyticsForFeatureChange(key, value, {
          status: 'Failure',
          failureReason: err.errors?.[0] || '',
        });
      });
    }
  };

  saveEmailConfig = (e) => {
    e.preventDefault();
    const {
      data: requestData,
      emailShown,
      emailOptional,
    } = this.getEmailConfigUpdateRequestPayload();

    if (!Object.keys(requestData.features).length) {
      // no update required Already updated
      this.props.showNotification({
        type: 'success',
        message: 'Configuration updated',
      });
      return;
    }

    if (this.props.email_config === EmailLessCheckoutConfigOptions.REQUIRED) {
      this.context
        .confirm({
          header: "Are you sure you want to collect the customer's e-mail address on checkout?",
          message: () => (
            <div className="text-semi-muted">
              <p>
                Collecting additional information from the user that is not necessary might result
                in increased drop-off on checkout
              </p>
            </div>
          ),
          affirmativeLabel: 'Yes, collect email',
          affirmativePendingLabel: 'Updating...',
          abortLabel: "No, don't collect",
          abort: () => {},
          action: () => {
            this.updateEmailConfig({
              emailOptional,
              emailShown,
              requestData,
            });
          },
        })
        .catch(() => {});
      return;
    }
    this.updateEmailConfig({
      emailOptional,
      emailShown,
      requestData,
    });
  };

  getPreviewImage = (emailLessSettings) => {
    switch (emailLessSettings) {
      case EmailLessCheckoutConfigOptions.NO:
        return EmailHiddenPreviewImage;
      case EmailLessCheckoutConfigOptions.OPTIONAL:
        return EmailOptionalPreviewImage;
      default:
        return EmailRequiredPreviewImage;
    }
  };

  saveLocale = (e) => {
    e.preventDefault();
    selfServeTrackInitiate({
      selfServeAction: 'Language Changed',
      page: 'Config',
      screen: 'Settings',
    });
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
      selfServeTrackSuccess({
        selfServeAction: 'Language Changed',
        page: 'Config',
        screen: 'Settings',
      });
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

  onBiggerFileSize = (_) => {
    const { showNotification } = this.props;
    showNotification({
      type: 'error',
      message: `Image too large. Max limit ${THUMBNAIL_SIZE_LIMIT / (1024 * 1024)}MB`,
    });
  };

  addFile = (file) => {
    if (file) {
      const reader = new FileReader();
      reader.onload = (e) => {
        this.setState({
          rectangularImageFile: {
            name: file.name,
            file: e.target.result,
          },
        });
      };

      reader.readAsDataURL(file);
    }
  };

  removeRectangularImage = () => {
    this.setState({
      rectangularImageFile: null,
    });
  };

  closeImageCropperModal = () => {
    this.setState({
      rectangularImageFile: null,
    });
  };

  removeRectangularLogo = () => {
    const { showNotification, config, removeLogo } = this.props;
    const payLoad = { ...config, isRectangularLogo: true };
    this.setState({ isLoading: true });
    return removeLogo(payLoad)
      .then(() => {
        showNotification({
          type: SUCCESS,
          message: LOGO_REMOVED_SUCCESSFULLY,
        });
      })
      .catch(({ errors }) => {
        showNotification({
          type: ERROR,
          message: errors,
        });
      })
      .finally(() => {
        this.setState({ isLoading: false });
      });
  };

  render() {
    const { textClr, colorVariations, isLoading, rectangularImageFile } = this.state;
    const {
      user,
      i18: { isConfigTagEnabled },
      config,
    } = this.props;
    const { rect_logo_url } = config;
    const isEnabled = user.isFeatureEnabled('covid_19_relief');
    return (
      <div className="panel panel-default panel-theme">
        <div className="panel-section--theme">
          <div className="panel-heading">
            <span className="title">
              <TextHighlighter hashedWith={ACCOUNT_SETTINGS}>Account Settings</TextHighlighter>
            </span>
          </div>
          <div className="panel-body">
            <form className="form-horizontal">
              <div className="form-group theme-select">
                {user.isCovidReliefFlowEnabled &&
                  user.business_type !== 7 &&
                  user.business_type !== 9 && (
                    <div className="covid-donations__settings">
                      {this.props.isLoading ? (
                        <LoaderDots />
                      ) : (
                        <div
                          className={
                            user.isFeatureEnabled('covid_19_relief') ? 'text-primary' : 'text-faded'
                          }
                        >
                          <i className="i i-Donate" />
                          <strong>
                            Donations{' '}
                            {user.isFeatureEnabled('covid_19_relief') ? `enabled` : `disabled`} on
                            Checkout
                          </strong>
                          <span className="toggler-btn">
                            <SwitchField
                              defaultChecked={!!isEnabled}
                              onChange={this.props.onSwitchChange}
                              type="prime"
                            />
                            {user.isFeatureEnabled('covid_19_relief') ? (
                              <b className="text-primary">Enabled</b>
                            ) : (
                              <b className="text-faded">Disabled</b>
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

                {user.isAccountAndSettingsRevampEnabled && <BrandName />}

                <label className="col-md-12 col-sm-12" style={{ marginTop: 12 }}>
                  <strong>Theme Color</strong>
                </label>
                <div className="col-md-5 col-sm-6" style={{ position: 'relative' }}>
                  <div className="color-picker">
                    <Field
                      name="brand_color"
                      component="input"
                      className="form-control"
                      type="color"
                      onChange={this.onChangeBrandColor}
                    />
                  </div>
                  <Field
                    name="brand_color"
                    component="input"
                    className="form-control"
                    onChange={this.onChangeBrandColor}
                  />
                </div>
                <div className="col-md-3 col-sm-6">
                  <AsyncButton
                    className="btn btn-primary"
                    text="Save"
                    pendingText="Saving..."
                    onClick={this.onSave}
                  />
                </div>
                <div className="col-md-12 col-md-6 description">
                  Choose a theme color for your brand.
                  <br />
                  The default theme color will be used if none is specified.
                </div>
              </div>

              <div className="form-group">
                <label className="col-md-12" style={{ marginTop: 12 }}>
                  <strong>Your Logo</strong>
                </label>
                <div className="col-md-12 media" style={{ marginTop: 0 }}>
                  {this.props.config.logo_url && (
                    <div className="media-left">
                      <a>
                        <img
                          className="media-object"
                          src={this.props.config.logo_url}
                          width="72"
                          height="72"
                        />
                      </a>
                    </div>
                  )}

                  <div className="media-body">
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

                    <div className="help-block" style={{ marginBottom: 0 }}>
                      <i style={{ fontSize: 12 }}>Max file size: 1MB</i>
                    </div>
                  </div>
                  <div className="description">
                    Choose a square image of minimum dimensions 256x256 px.
                  </div>
                </div>
              </div>
              {user.isCustomMerchantUPIQR && (
                <Box marginY="20px">
                  <Box marginBottom="10px">
                    <Text weight="semibold" size="large">
                      Rectangular Logo
                    </Text>
                  </Box>
                  <Box
                    display="flex"
                    flexDirection={{
                      base: 'column',
                      m: 'row',
                    }}
                    marginBottom="10px"
                  >
                    <Box flex="1">
                      <Box>
                        <FileUpload
                          accept={FILE_TYPES}
                          size="large"
                          uploadedFileName={UPLOAD_IMAGE_HERE}
                          maxSize={THUMBNAIL_SIZE_LIMIT}
                          onBiggerFileSize={this.onBiggerFileSize}
                          onFileChange={this.addFile}
                          onCloseClick={this.removeRectangularImage}
                          defaultValue={rect_logo_url}
                          files={[]}
                          imgFilePreviewUrl={rect_logo_url}
                          showFileSize={true}
                          name="rect-logo-file-upload"
                        >
                          <Box className="Dropzone-80g-details">
                            <BladeButton variant="primary" icon={FolderIcon}>
                              Choose File
                            </BladeButton>{' '}
                          </Box>
                        </FileUpload>
                      </Box>
                    </Box>
                    <Box flex="1" marginLeft="20px" marginTop="5px">
                      {rect_logo_url && (
                        <BladeButton
                          isLoading={isLoading}
                          variant="primary"
                          onClick={this.removeRectangularLogo}
                        >
                          Remove
                        </BladeButton>
                      )}
                    </Box>
                  </Box>

                  <Box marginBottom="10px">
                    <Text marginBottom="5px">
                      Choose a rectangular image of minimum height 60px.
                    </Text>
                    <Text>Upload .png, .jpg or .jpeg file | 1 MB Max</Text>
                  </Box>

                  {rectangularImageFile && (
                    <ImageCropperModal
                      rectangularImageFile={rectangularImageFile}
                      closeModal={this.closeImageCropperModal}
                    />
                  )}
                </Box>
              )}

              <IntoView hashedWith={CHECKOUT_LANG}>
                {this.props.locale && (
                  <div className="form-group">
                    <label className="col-md-12" style={{ marginTop: 12 }}>
                      <strong>
                        <TextHighlighter hashedWith={CHECKOUT_LANG}>
                          Default Language
                        </TextHighlighter>
                      </strong>
                    </label>
                    <div className="col-md-6" style={{ marginTop: 0 }}>
                      <select
                        className="form-control"
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
                    <div className="col-md-3 col-sm-6 language-option-button">
                      <AsyncButton
                        className="btn btn-primary"
                        text="Save"
                        pendingText="Saving..."
                        onClick={this.saveLocale}
                      />
                    </div>
                    <div className="col-md-12 mt-12">
                      <br />
                      Default language will be used on the Checkout page if customer doesn’t specify
                      a language.
                    </div>
                  </div>
                )}
              </IntoView>
              <div className="mt-12">
                <IntoView hashedWith={CHECKOUT_EMAIL_SETTINGS}>
                  <div className="form-group">
                    <label className="col-md-12 mt-12">
                      <strong>
                        <TextHighlighter hashedWith={CHECKOUT_EMAIL_SETTINGS}>
                          Collect email address from users on Checkout page
                        </TextHighlighter>
                      </strong>
                    </label>
                    <div className="col-md-6">
                      <select
                        className="form-control"
                        defaultValue={this.props.email_config}
                        onChange={this.onChangeEmailConfig}
                      >
                        {checkoutEmailConfigOptions.map((config) => (
                          <option key={config.code} value={config.code}>
                            {config.name}
                          </option>
                        ))}
                      </select>
                    </div>
                    <div className="col-md-3 col-sm-6 language-option-button">
                      <AsyncButton
                        className="btn btn-primary"
                        text="Save"
                        pendingText="Saving..."
                        onClick={this.saveEmailConfig}
                      />
                    </div>
                  </div>
                </IntoView>
              </div>
            </form>
            <ShowWhen additionalCondition={() => !isConfigTagEnabled('settings.checkout_info')}>
              <div className="footer-note">
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
                  {''}.
                </ShowWhen>
              </div>
            </ShowWhen>
          </div>
        </div>
        <div className="panel-section--checkout">
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
                    <div id="amount">{getCurrencySymbol(user.merchant.currency)} 1</div>
                  </div>
                )}
              </div>
            </div>
            <div id="preview-checkout-form">
              <img src={this.getPreviewImage(this.props.email_config)} width="100%" />
              <div id="payment-method-icons">
                {['card', 'netbanking', 'wallet', 'upi', 'emi', 'qr'].map((type, ix) => (
                  <span key={ix}>
                    {getIcon(type, colorVariations)}
                    <span className="payment-method-label">{type}</span>
                  </span>
                ))}
              </div>
            </div>
          </div>

          {user.isCustomMerchantUPIQR && config?.preview_image_url?.signed_url && (
            <Box id="preview-qr">
              <Box>
                <img
                  alt="QR Preview"
                  className="base-qr-template"
                  src={config?.preview_image_url?.signed_url}
                />
              </Box>
            </Box>
          )}

          <EasterEgg extraClass="ftx-settings-page" page="Settings" />
        </div>
      </div>
    );
  }
}

export default compose(
  connect((state) => ({ ...state.config, user: state.session.user, org: state.session.org }), {
    uploadLogo,
    removeLogo,
    showNotification,
    fetchLocale,
    updateFeatures,
    updateLocale,
    updateEmailConfig,
    saveLocale,
    ...ModalActions,
  }),
  reduxForm({}),
)(withI18Service(CheckoutTheme));
