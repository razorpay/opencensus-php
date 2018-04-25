import { connect } from 'react-redux';
import { merchantFetch } from 'rzp/utils/ajax';
import { showNotification } from 'rzp/modules/notifications';
import { without } from 'rzp/utils/rzp-utils';
import Spinner from 'rzp/ui/Spinner';
import ActivationWizard from 'component/merchant/Activation';

export default class ActivationContainer extends React.Component {
  state = {
    data: null,
    categories: null
  }

  componentWillMount() {
    this.fetchActivationDetails();
  }

  fetchActivationDetails() {
    Promise.all([
      merchantFetch({
        url: 'merchant/activation',
        mode: 'live'
      }),
      merchantFetch('merchant/activation/business_categories')
    ])
    .then(([data, categories]) => {
      this.setState({
        data: data.data,
        categories: categories.data
      })
    })
  }

  saveStep = (data, accountId) => {
    return merchantFetch({
      url: 'merchant/activation',
      mode: 'live',
      method: 'post',
      data
    })
    return this._save(props, accountId).catch(err => {
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
