import { Component } from 'react';
import { compose } from 'redux';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { Field, reduxForm } from 'redux-form';
import ModalHeader from 'common/ui/ModalHeader';
import Alert from 'common/ui/Forms/Alert';
import { closeModal } from 'merchant_common/reducers/modals';
import { showNotification } from 'merchant_common/reducers/notifications';
import ajax from 'merchant/utils/ajax';
import fileDownload from 'common/utils/file-download';
import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';

class NewKey extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };

  constructor(props) {
    super(props);
    this.state = {
      errors: null,
    };
  }

  UNSAFE_componentWillMount() {
    const key = this.props.apiKey;
    if (key) {
      this.props.initialize({
        keyId: key.id,
        keySecret: key.secret,
      });
    }
  }

  save = () => {
    this.context.confirm({
      message:
        'Are you sure you have saved the key details? ' +
        'This is the last time we will show you the key secret.',
      affirmativeLabel: 'OK',
      action: () => this.props.closeModal(),
    });
  };

  handleDownloadToken = () => {
    const { apiKey: key } = this.props;

    return ajax({
      url: '/keys/csv',
      method: 'post',
      appendModeInQueryParam: true,
      data: {
        id: key.id,
        secret: key.secret,
      },
    })
      .then((data) => {
        fileDownload(data, 'rzp.csv');
      })
      .catch(({ errors }) => {
        this.props.showNotification({
          type: 'error',
          message: errors[0],
        });
      });
  };

  render() {
    const { handleSubmit } = this.props;

    analyticsTrack({
      objectName: 'new key popup',
      actionName: 'displayed',
      screen: 'settings',
      properties: {
        location: 'API Keys',
        ...getCommonAnalyticsProperties(window.rzp_user),
      },
    });

    return (
      <div>
        <ModalHeader title="New Key" onCloseClick={handleSubmit(this.save)} />

        <Alert type="error" message={this.state.errors} />

        <form class="form-horizontal payment-link-form" onSubmit={handleSubmit(this.save)}>
          <div class="modal-body">
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Key Id</div>
              </label>
              <div class="col-md-8">
                <Field name="keyId" component="input" class="form-control" readOnly="readonly" />
              </div>
            </div>
            <div class="form-group">
              <label class="col-md-3 control-label">
                <div>Key Secret</div>
              </label>
              <div class="col-md-8">
                <Field
                  name="keySecret"
                  component="input"
                  class="form-control"
                  readOnly="readonly"
                />
              </div>
            </div>
            <div class="form-group">
              <div class="col-md-8 col-md-offset-3">
                <button class="btn-link no-padding" onClick={this.handleDownloadToken}>
                  Download Key Details
                </button>
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn btn-primary" onClick={handleSubmit(this.save)}>
              OK
            </button>
          </div>
        </form>
      </div>
    );
  }
}

export default compose(
  connect((state) => state.session, { closeModal, showNotification }),
  reduxForm({
    form: 'newKeyModal',
  }),
)(NewKey);
