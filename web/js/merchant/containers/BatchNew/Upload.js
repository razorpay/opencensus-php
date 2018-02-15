import { Component } from 'react';
import { connect } from 'react-redux';

import BatchUploadModal from 'merchant/components/BatchNew/UploadModal';

import { uploadPaymentLinkBatch as uploadBatch } from 'merchant/modules/batches';

@connect(state => state.session, { uploadBatch })
export default class BatchUpload extends Component {
  state = {
    loadMore: false,
    notification: null,
    //TODO: remove after file component is added
    file: null,
  };

  //TODO: remove after file component is added
  handleFileChange = e => {
    console.log(e.target.files[0]);
    this.setState({ file: e.target.files[0] }, this.handleFileUpload);
  };

  handleFileUpload = () => {
    this.props
      .uploadBatch(this.state.file, this.props.mode)
      .then(response => {
        console.log('Response: ', response);
      })
      .catch(({ errors }) => {
        console.log('Errors: ', errors);
      });
  };

  handleLoadMore = () => {
    this.setState({
      loadMore: !this.state.loadMore,
    });
  };

  render() {
    return (
      <BatchUploadModal
        loadMore={this.state.loadMore}
        notification={this.state.notification}
        onLoadMore={this.handleLoadMore}
        onFileChange={this.handleFileChange}
        {...this.props}
      />
    );
  }
}
