import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { ModalContent } from 'common/new-ui/Modal';
import adminFetch from 'razorx/helpers/admin-fetch';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField, FileField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

@withRouter
export default class AddEditSegment extends React.Component {
  state = {
    isSaving: false,
    fileId: null,
  };

  onSubmit = (form) => {
    const isInvalid = this.isInvalid(form);

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    const payload = {
      segment: {
        name: form.name,
        description: form.description,
        falsePositivityRate: '0.000001',
        inputFileID: this.state.fileId,
      },
    };

    const segmentUrl = 'segment.v1.SegmentAPI/Create';
    const successMsg = `Segment is successfully created`;

    this.setState({ isSaving: true });

    splitzFetch({ url: segmentUrl, data: payload })
      .then((response) => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();
        this.props.collection.fetch();
        this.props.history.push(`/splitz/segments/${response.group.id}`);
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  isInvalid() {
    if (!this.state.fileId) {
      return 'Input file should be uploaded';
    }

    return false;
  }

  handleFileChange = (event) => {
    event.preventDefault();
    const file = event.currentTarget.files[0];

    const bodyFormData = new FormData();
    bodyFormData.append('mode', 'live');
    bodyFormData.append('method', 'POST');
    bodyFormData.append('auth', 'admin');
    bodyFormData.append('content_type', 'multipart/form-data');
    bodyFormData.append('file_name', 'file');
    bodyFormData.append('body', `name=${file.name}&type=splitz_segment&entity=`);
    bodyFormData.append('file', file);

    this.setState({ isSaving: true });

    adminFetch({
      url: '/makeapicall/admin-ufh/file/upload',
      method: 'POST',
      data: bodyFormData,
      headers: { 'Content-Type': 'multipart/form-data' },
    })
      .then((res) => {
        this.setState({ fileId: res.file_id, isSaving: false });
      })
      .catch((err) => {
        console.error('File Upload Error: ', err);
        this.setState({ fileId: null, isSaving: false });
      });
  };

  render() {
    const { isSaving } = this.state;
    const header = 'Create Segment';
    const isLoading = isSaving;

    return (
      <ModalContent class="modal-features modal-json-edit" header={header}>
        <Form
          onSubmit={this.onSubmit}
          class="full-span full-elements"
          style={{ opacity: isLoading ? 0.5 : 1 }}
        >
          <React.Fragment>
            {isLoading && <div className="spinner center" />}
            <Field
              type="text"
              label="Name"
              name="name"
              placeholder="Segment Name"
              defaultValue=""
              required
            />
            <TextAreaField
              label="Description"
              name="description"
              placeholder="Segment Description"
              defaultValue=""
              required
            />
            <FileField
              label="Input File"
              id="inputFile"
              name="inputFile"
              required
              onChange={this.handleFileChange}
            />
            <div style={{ marginTop: 24 }} />
          </React.Fragment>
          <div className="footer">
            <button className="btn btn--primary">
              Create Segment
              <span className="spin-btn" />
            </button>
          </div>
        </Form>
      </ModalContent>
    );
  }
}

AddEditSegment.defaultProps = {};

AddEditSegment.propTypes = {
  collection: PropTypes.object.isRequired,
  history: PropTypes.object.isRequired,
};
