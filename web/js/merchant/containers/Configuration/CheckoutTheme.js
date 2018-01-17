import { Component } from 'react';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import AsyncButton from 'react-async-button';
import FileUploadButton from 'rzp/ui/FileUpload/Button';
import { uploadLogo } from 'merchant/modules/config';
import { showNotification } from 'rzp/modules/notifications';

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

  render() {
    let { handleSubmit } = this.props;

    return (
      <div class="panel panel-default">
        <div class="panel-heading">Checkout Theme</div>
        <div class="panel-body">
          <form class="form-horizontal">
            <div class="form-group">
              <label class="col-md-12">
                <strong>Theme Color</strong>
              </label>
              <div class="col-md-2 col-sm-3">
                <Field
                  name="brand_color"
                  component="input"
                  class="form-control"
                  type="color"
                />
              </div>
              <div class="col-md-3 col-sm-6">
                <Field
                  name="brand_color"
                  component="input"
                  class="form-control"
                />
              </div>
              <div class="col-md-12 help-block">
                Choose a theme color to customize the checkout form. The default
                theme color will be used if none is specified.{' '}
                <b>Use the color picker or enter the hexadecimal color code</b>
              </div>
            </div>

            <div class="form-group">
              <label class="col-md-12">
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
                    <i>Max file size: 1MB</i>
                  </div>
                </div>
                <div>
                  <small class="help-block">
                    Upload your logo that will appear on the checkout form.
                    Choose a square image of minimum dimensions 256x256 px.
                  </small>
                </div>
              </div>
            </div>

            <hr />

            <div class="btn-toolbar">
              <AsyncButton
                class="btn btn-default pull-right"
                text="Save Changes"
                pendingText="Saving..."
                onClick={handleSubmit(this.props.onSave)}
              />
            </div>
          </form>
        </div>
      </div>
    );
  }
}
