import { Component } from 'react';
import { connect } from 'react-redux';
import { reduxForm, Field } from 'redux-form';
import Header from 'rzp/ui/Header';
import { uploadBatchRefunds } from 'merchant/modules/refunds/batchuploads';

@connect(null, { uploadBatchRefunds })
@reduxForm({
  name: 'uploadBatchRefunds'
})
export default class BatchUpload extends Component {
  render() {
    return (
      <div class="react-root">
        <Header title="Batch Upload" />

        <div class="content-wrapper">
          <div class="panel panel-default">
            <div class="panel-heading">
              Refunds File Upload
            </div>
          </div>
        </div>
      </div>
    );
  }
}
