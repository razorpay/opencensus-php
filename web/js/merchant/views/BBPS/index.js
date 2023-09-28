import { merchantFetch } from 'merchant/utils/ajax';
import { connect } from 'react-redux';

import { showNotification } from 'merchant_common/reducers/notifications';
import React from 'react';

const ERROR_MESSAGE = 'Something went wrong, try again later!';
@connect(null, {
  showNotification,
})
export default class BBPS extends React.PureComponent {
  constructor(props) {
    super(props);
    this.state = {
      iframeUrl: '',
    };
  }

  componentDidMount() {
    merchantFetch({
      url: 'bbps_bill_payments',
      method: 'get',
    })
      .then((response) => {
        if (response.data.iframe_embed_url) {
          this.setState({
            iframeUrl: response.data.iframe_embed_url,
          });
        } else {
          // error case UI to be modified in later phases
          this.props.showNotification({
            type: 'error',
            message: ERROR_MESSAGE,
          });
        }
      })
      .catch((error) => {
        this.props.showNotification({
          type: 'error',
          message: error.errors[0] || ERROR_MESSAGE,
        });
      });
  }

  render() {
    return (
      <div class="Bbps-container">
        {this.state.iframeUrl ? <iframe src={this.state.iframeUrl} /> : <div class="spinner" />}
      </div>
    );
  }
}
