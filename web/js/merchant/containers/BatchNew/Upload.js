import { Component } from 'react';
import { connect } from 'react-redux';

import BatchUploadModal from 'merchant/components/BatchNew/UploadModal';

import { uploadPaymentLinkBatch as uploadBatch } from 'merchant/modules/batches';

export default class BatchUpload extends Component {
  state = {
    loadMore: false,
  };
  componentWillMount() {}

  handleLoadMore = () => {
    this.setState({
      loadMore: !this.state.loadMore,
    });
  };

  render() {
    return (
      <BatchUploadModal
        loadMore={this.state.loadMore}
        onLoadMore={this.handleLoadMore}
        {...this.props}
      />
    );
  }
}
