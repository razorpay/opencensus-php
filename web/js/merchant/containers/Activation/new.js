import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import Spinner from 'rzp/ui/Spinner';
import Modal from 'component/Modal';
import ActivationWizard from 'component/merchant/Activation';

import { withRouter } from 'react-router-dom';

@withRouter
@connect(
  state => {
    return {};
  },
  {
    showNotification,
  }
)
export default class ActivationContainer extends React.Component {
  state = {
    data: null,
    categories: null,
  };

  componentWillMount() {
    this.fetchActivationDetails();

    if (this.props.closeUrl) {
      this.state.isModal = true;
    }
  }

  fetchActivationDetails() {
    Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        mode: 'live',
      }),
      merchantFetch('merchant/activation/business_categories'),
    ]).then(([data, categories]) => {
      this.setState({
        data: data.data,
        categories: categories.data,
      });
    });
  }

  submitForm = (data, accountId) => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data: { submit: 1 },
    })
      .then(response => {
        if (!response.data.can_submit) {
          throw { errors: ['Some mandatory fields are required'] };
        }
        return response;
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        throw err;
      });
  };

  saveStep = (data, accountId) => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data,
    }).catch(err => {});
  };

  saveFile = (event, fieldName, accountId) => {
    let files = event.target.files;
    let file = files[0];

    let formData = new FormData();

    let fieldNameMapping = {
      business_proof_url: 'business_proof_url',
      business_operation_proof: 'business_operation_proof_url',
      business_pan_url: 'business_pan_url',
      address_proof_url: 'address_proof_url',
      promoter_proof: 'promoter_proof_url',
      promoter_pan_proof: 'promoter_pan_url',
      promoter_address_url: 'promoter_address_url',
      ngo_12a_proof: 'form_12a_url',
      ngo_80g_proof: 'form_80g_url',
    };
    formData.append(fieldNameMapping[fieldName], file);

    // TODO: Temporary notification in then-catch, success-error msg would be adjusted in custom UI for file upload.
    return merchantFetch({
      url: 'merchant/activation/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId,
    })
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'File uploaded successfully',
        });
      })
      .catch(err => {});
  };

  handleClose = e => {
    // this.props.history.goBack();
    this.props.history.replace(this.props.closeUrl);
  };

  render() {
    let { data, categories } = this.state;

    const content = data ? (
      <ActivationWizard
        data={data}
        categories={categories}
        save={this.saveStep}
        saveFile={this.saveFile}
        submitForm={this.submitForm}
      />
    ) : (
      <div class="page-spinner-container">
        <Spinner />
      </div>
    );

    return this.state.isModal ? (
      <Modal onClose={this.handleClose}>{content}</Modal>
    ) : (
      <div class="activation-container">{content}</div>
    );
  }
}
