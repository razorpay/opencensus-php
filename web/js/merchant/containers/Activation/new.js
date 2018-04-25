import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import Spinner from 'rzp/ui/Spinner';
import ActivationWizard from 'component/merchant/Activation';

@connect(state => {}, {
  showNotification,
})
export default class ActivationContainer extends React.Component {
  state = {
    data: null,
    categories: null,
  };

  componentWillMount() {
    this.fetchActivationDetails();
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
    })
      .then(response => {
        if (response.data) {
          this.setState({
            data,
          });
        }
      })
      .catch(err => {
        this.props.showNotification({
          type: 'error',
          message: err.errors,
        });

        throw err;
      });
  };

  saveFile = (event, fieldName, accountId) => {
    let files = event.target.files;

    // TODO: Temporary notification in then-catch, success-error msg would be adjusted in custom UI for file upload.
    return this.props
      .saveFile({
        fieldName,
        file: files[0],
        accountId,
      })
      .then(response => {
        this.props.showNotification({
          type: 'success',
          message: 'File uploaded successfully',
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
    let { data, categories } = this.state;

    return (
      <div>
        {data ? (
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
        )}
      </div>
    );
  }
}
