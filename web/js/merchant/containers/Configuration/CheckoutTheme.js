import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import FileUploadButton from 'rzp/ui/FileUpload/Button';
import { uploadLogo } from 'merchant/reducers/config';
import { showNotification } from 'merchant_common/reducers/notifications';
import ShowWhen from 'merchant/components/ShowWhen';
import { getIcon } from './paymentMethodIcons';

@connect(state => ({ ...state.config, user: state.session.user }), {
  uploadLogo,
  showNotification,
})
@reduxForm({})
export default class CheckoutTheme extends Component {
  state = { brandColor: this.props.config.brand_color };

  componentWillMount() {
    this.props.initialize(this.props.config);

    const script = document.createElement('script');

    script.onload = () => {
      this.updatePreviewTextClr();
    };

    script.src = 'https://cdn.razorpay.com/static/assets/color.js';

    document.head.appendChild(script);
  }

  updatePreviewTextClr() {
    const textClr =
      !window.colorLib || window.colorLib.isDark(this.state.brandColor)
        ? '#fff'
        : 'rgba(0, 0, 0, 0.85)';

    const colorVariations = window.colorLib.getColorVariations(
      this.state.brandColor
    );

    this.setState({
      textClr,
      colorVariations,
    });
  }

  uploadLogo = event => {
    let file = event.target.files[0];
    return this.props
      .uploadLogo(file, 'logo')
      .then(() => {
        this.props.showNotification({
          type: 'success',
          message: 'File Uploaded Successfully',
        });
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors,
        });
      });
  };

  onSave = e => {
    this.analytics();
    this.props.handleSubmit(this.props.onSave)(e);
  };

  analytics = () => {
    window.rzpAnalytics({
      eventCategory: 'Dashboard - Settings',
      eventAction: 'Change - Checkout Theme',
    });
  };

  onChangeBrandColor = e => {
    this.setState({ brandColor: e.target.value });

    this.updatePreviewTextClr();
  };

  render() {
    const { textClr, colorVariations } = this.state;

    return (
      <div class="panel panel-default panel-theme">
        <div class="panel-section--theme">
          <div class="panel-heading">
            <span className="title">Account Settings</span>
          </div>
          <div class="panel-body">
            <form class="form-horizontal">
              <div class="form-group theme-select">
                <label
                  className="col-md-12 col-sm-12"
                  style={{ marginTop: 12 }}
                >
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
                    text="Save Changes"
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
                      text="Choose File"
                      labelClass="btn-primary"
                      accept="image/jpeg,image/jpg,image/png"
                      maxSize="1048576"
                      onChange={this.uploadLogo}
                    />
                    <div class="help-block" style={{ marginBottom: 0 }}>
                      <i style={{ fontSize: 12 }}>Max file size: 1MB</i>
                    </div>
                  </div>
                  <div class="description">
                    Choose a square image of minimum dimensions 256x256 px.
                  </div>
                </div>
              </div>
            </form>
            <div class="footer-note">
              Changes will reflect on{' '}
              <ShowWhen
                additionalCondition={user =>
                  user.isOrgAllowedFunctionality('external_links')
                }
              >
                <a target="_blank" href="https://razorpay.com/payment-gateway/">
                  Checkout page
                </a>,{' '}
                <a target="_blank" href="https://razorpay.com/payment-links/">
                  Payment Links
                </a>,{' '}
                <a target="_blank" href="https://razorpay.com/invoices/">
                  Invoices
                </a>{' '}
                &{' '}
                <a target="_blank" href="https://razorpay.com/payment-pages">
                  Payment pages
                </a>.
              </ShowWhen>
            </div>
          </div>
        </div>
        <div class="panel-section--checkout">
          <div id="preview-label">Preview</div>
          <div id="preview-checkout">
            <div
              id="checkout-header"
              style={{ backgroundColor: this.state.brandColor }}
            >
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
                {['card', 'netbanking', 'wallet', 'upi', 'emi', 'qr'].map(
                  (type, ix) => (
                    <span key={ix}>
                      {getIcon(type, colorVariations)}
                      <span class="payment-method-label">{type}</span>
                    </span>
                  )
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }
}
