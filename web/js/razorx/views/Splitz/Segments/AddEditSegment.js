import React from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';
import { ModalContent } from 'common/new-ui/Modal';
import adminFetch from 'razorx/helpers/admin-fetch';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField, FileField, SelectField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';

const CSV = 'CSV';
const SQL = 'SQL';

@withRouter
export default class AddEditSegment extends React.Component {
  state = {
    isSaving: false,
    fileId: null,
    segmentType: CSV,
  };

  onSubmit = (form) => {
    const isInvalid = this.isInvalid(form);

    if (!!isInvalid) {
      notifyError(isInvalid);
      return;
    }

    const type = this.state.segmentType;

    const payload = {
      segment: {
        name: form.name,
        description: form.description,
        falsePositivityRate: '0.000001',
      },
    };

    if (type === SQL) {
      payload.segment.cron_expression = form.cronExpression;
      payload.segment.sql_query = form.sqlQuery;
    } else {
      payload.segment.inputFileID = this.state.fileId;
    }
    payload.segment.source_type = this.state.segmentType;

    const segmentUrl = 'segment.v1.SegmentAPI/Create';
    const successMsg = `Segment is successfully created`;

    this.setState({ isSaving: true });

    splitzFetch({ url: segmentUrl, data: payload })
      .then((response) => {
        this.setState({ isSaving: false });
        notifySuccess(successMsg);
        closeModal();
        this.props.collection.fetch();
        this.props.history.push(`/splitz/segments/${response.items.id}`);
      })
      .catch((err) => {
        this.setState({ isSaving: false });
        notifyError(err);
      });
  };

  isInvalid(form) {
    if (this.state.segmentType === CSV && !this.state.fileId) {
      return 'Input file should be uploaded';
    }

    if (this.state.segmentType === SQL) {
      if (!form.cronExpression || !form.sqlQuery) {
        return 'Cron Expression and SQL Query must not be empty';
      }

      const cronRegex = new RegExp(/^([0-5]?[0-9])\s+([0-9]|[0-1][0-9]|2[0-3])\s/);
      if (!cronRegex.test(form.cronExpression)) {
        return 'Cron Expression frequency must have a minimum interval of one day';
      }

      const sqlRegex = new RegExp(/(^SELECT [a-z0-9_]{1,20} FROM [A-Z0-9_,\s.=><"'()]*)$/i);
      if (!sqlRegex.test(form.sqlQuery)) {
        return "SQL Query must be in the format, 'SELECT column_name FROM ...'";
      }
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
    const typeDropdownProperties = [
      {
        label: CSV,
        value: CSV,
      },
      {
        label: SQL,
        value: SQL,
      },
    ].map((op, i) => (
      <option key={i} value={op.value}>
        {op.label}
      </option>
    ));

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
            <SelectField
              name="type"
              label="Type"
              defaultValue="CSV"
              required
              onChange={(evt) => {
                const type = evt.target.value;
                this.setState({ segmentType: type });
              }}
            >
              {typeDropdownProperties}
            </SelectField>
            {this.state.segmentType === SQL && (
              <div>
                <TextAreaField label="SQL Query" name="sqlQuery" placeholder="SQL Query" required />
                <Field type="text" label="Cron Expression" name="cronExpression" required />
                <p>
                  Use{' '}
                  <a
                    href="https://www.freeformatter.com/cron-expression-generator-quartz.html"
                    target="_blank"
                    style={{ color: 'blue' }}
                    rel="noreferrer noopener"
                  >
                    this link
                  </a>{' '}
                  to generate cron expression.{' '}
                  <a
                    href="https://docs.google.com/document/d/176KNlWrlDofaOGegwAUC9bCYlZ6F4b9FvSRHi7BcDDs"
                    target="_blank"
                    style={{ color: 'blue' }}
                    rel="noreferrer noopener"
                  >
                    Click here
                  </a>{' '}
                  to see recommendations and more.
                </p>
              </div>
            )}
            {this.state.segmentType === CSV && (
              <FileField
                label="Input File"
                id="inputFile"
                name="inputFile"
                required={this.state.segmentType === CSV}
                onChange={this.handleFileChange}
              />
            )}
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
