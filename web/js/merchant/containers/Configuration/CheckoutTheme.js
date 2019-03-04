import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import FileUploadButton from 'rzp/ui/FileUpload/Button';
import { uploadLogo } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';
import ShowWhen from 'merchant/components/ShowWhen';

@connect(state => state.config, { uploadLogo, showNotification })
@reduxForm({})
export default class CheckoutTheme extends Component {
  componentWillMount() {
    this.props.initialize(this.props.config);
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

  render() {
    return (
      <div class="panel panel-default">
        <div class="panel-heading">
          <span className="title">Checkout Theme</span>
        </div>
        <div class="panel-body">
          <form class="form-horizontal">
            <div class="form-group">
              <label class="col-md-12 m-t">
                <strong>Theme Color</strong>
              </label>
              <div class="col-md-3 col-sm-6" style={{ position: 'relative' }}>
                <div class="color-picker">
                  <Field
                    name="brand_color"
                    component="input"
                    class="form-control"
                    type="color"
                  />
                </div>
                <Field
                  name="brand_color"
                  component="input"
                  class="form-control"
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
              <div class="col-md-12 description">
                Choose a theme color for your brand.
                <br />
                The default theme color will be used if none is specified.
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-12 m-t">
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
                  <div class="help-block">
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
              </a>,{' '}
              <a target="_blank" href="https://razorpay.com/payment-pages">
                Payment pages
              </a>.
            </ShowWhen>
          </div>
        </div>
      </div>
    );
  }
}
