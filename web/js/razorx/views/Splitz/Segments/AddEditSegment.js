import React, { useReducer } from 'react';
import PropTypes from 'prop-types';
import { ModalContent } from 'common/new-ui/Modal';
import adminFetch from 'razorx/helpers/admin-fetch';
import { closeModal, notifySuccess, notifyError } from 'razorx/components/Modal';
import Form from 'razorx/components/ui/Form';
import Field, { TextAreaField, FileField, SelectField } from 'razorx/components/ui/Field';
import { splitzFetch } from 'razorx/helpers/fetch';
import {
  CRON_EXPRESSION_GENERATOR,
  DYNAMIC_SEGMENTS_DOC,
  SEGMENT_CREATE_URL,
  SEGMENT_EDIT_URL,
  SEGMENT_UPLOAD_URL,
} from './constants';

const CSV = 'CSV';
const SQL = 'SQL';
const header = 'Create Segment';

const typeDropdownProperties = [CSV, SQL].map((item) => (
  <option key={item} value={item}>
    {item}
  </option>
));

function reducer(state, action) {
  const { type, payload } = action;
  return { ...state, [type]: payload };
}
export default function AddEditSegment(props) {
  const { isEdit, data } = props;

  const initialState = {
    isSaving: false,
    fileId: null,
    segmentType: data?.source_type || null,
    matchingSegmentName: null,
  };

  const [state, dispatch] = useReducer(reducer, initialState);

  const { isSaving, fileId, segmentType, matchingSegmentName } = state;

  function validateForm(form) {
    if (segmentType === CSV && !fileId && !data.inputFileID) {
      return 'Input file should be uploaded';
    }

    if (segmentType === SQL) {
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

    if (matchingSegmentName !== null) {
      return 'A segment with this name already exists.';
    }

    return false;
  }

  const onSubmit = (form) => {
    const invalid = validateForm(form);

    if (!!invalid) {
      notifyError(invalid);
      return;
    }

    const payload = {
      segment: {
        name: form.name,
        description: form.description,
        falsePositivityRate: '0.000001',
      },
    };

    if (segmentType === SQL) {
      payload.segment.cron_expression = form.cronExpression;
      payload.segment.sql_query = form.sqlQuery;
    } else if (segmentType === CSV && isEdit && !fileId) {
      payload.segment.inputFileID = data.inputFileID;
    } else {
      payload.segment.inputFileID = fileId;
    }
    payload.segment.source_type = segmentType;

    let segmentUrl = ``;
    let successMsg = ``;

    if (isEdit) {
      payload.segment.id = data.id;
      segmentUrl = SEGMENT_EDIT_URL;
      successMsg = 'Segment is successfully updated';
    } else {
      segmentUrl = SEGMENT_CREATE_URL;
      successMsg = 'Segment is successfully created';
    }

    dispatch({ type: 'isSaving', payload: true });

    splitzFetch({ url: segmentUrl, data: payload })
      .then(() => {
        dispatch({ type: 'isSaving', payload: false });
        notifySuccess(successMsg);
        closeModal();
        props?.collection?.fetch();
        if (isEdit) {
          location.reload();
        }
      })
      .catch((err) => {
        dispatch({ type: 'isSaving', payload: false });
        notifyError(err);
      });
  };

  const handleFileChange = (event) => {
    event.preventDefault();
    const file = event.currentTarget.files[0];

    const bodyFormData = new FormData();
    bodyFormData.append('mode', 'live');
    bodyFormData.append('method', 'POST');
    bodyFormData.append('auth', 'admin');
    bodyFormData.append('content_type', 'multipart/form-data');
    bodyFormData.append('file', file);
    bodyFormData.append('file_name', 'file');

    dispatch({ type: 'isSaving', payload: true });

    adminFetch({
      url: SEGMENT_UPLOAD_URL,
      method: 'POST',
      data: bodyFormData,
      headers: { 'Content-Type': 'multipart/form-data' },
    })
      .then((res) => {
        dispatch({ type: 'fileId', payload: res.file_id });
      })
      .catch((err) => {
        console.error('File Upload Error: ', err);
        dispatch({ type: 'fileId', payload: null });
      })
      .finally(() => {
        dispatch({ type: 'isSaving', payload: false });
      });
  };

  const checkForExistingSegmentName = (e) => {
    splitzFetch({
      url: 'segment.v1.SegmentAPI/GetByName',
      data: {
        segmentName: e.target.value,
      },
    })
      .then((res) => {
        dispatch({ type: 'matchingSegmentName', payload: res?.items?.name });
      })
      .catch(() => {
        dispatch({ type: 'matchingSegmentName', payload: null });
      });
  };

  const changeSegmentType = (e) => {
    const type = e.target.value;
    dispatch({ type: 'segmentType', payload: type });
  };

  const formOpacity = `form-submit-opacity-${isSaving ? 'low' : 'high'}`;

  return (
    <ModalContent className="modal-features modal-json-edit" header={header}>
      <Form onSubmit={onSubmit} className={`full-span full-elements ${formOpacity}`}>
        <React.Fragment>
          {isSaving && <div className="spinner center" />}
          <Field
            type="text"
            label="Name"
            name="name"
            placeholder="Segment Name"
            defaultValue={isEdit ? data.name : ''}
            required
            onChange={checkForExistingSegmentName}
          />
          {matchingSegmentName && (
            <span className="error-message">This segment name already exists.</span>
          )}
          <TextAreaField
            label="Description"
            name="description"
            placeholder="Segment Description"
            defaultValue={isEdit ? data.description : ''}
            required
          />
          <SelectField
            name="type"
            label="Type"
            defaultValue={isEdit ? data.source_type : 'CSV'}
            required
            onChange={changeSegmentType}
          >
            {typeDropdownProperties}
          </SelectField>
          {segmentType === SQL && (
            <div>
              <TextAreaField
                label="SQL Query"
                name="sqlQuery"
                placeholder="SQL Query"
                defaultValue={isEdit ? data?.sql_query : ''}
                required
              />
              <Field
                type="text"
                label="Cron Expression"
                name="cronExpression"
                defaultValue={isEdit ? data?.cron_expression : ''}
                required
              />
              <p>
                Use{' '}
                <a
                  href={CRON_EXPRESSION_GENERATOR}
                  target="_blank"
                  className="form-links"
                  rel="noreferrer noopener"
                >
                  this link
                </a>{' '}
                to generate cron expression.{' '}
                <a
                  href={DYNAMIC_SEGMENTS_DOC}
                  target="_blank"
                  className="form-links"
                  rel="noreferrer noopener"
                >
                  Click here
                </a>{' '}
                to see recommendations and more.
              </p>
            </div>
          )}
          {(segmentType === CSV || segmentType === null) && (
            <FileField
              label="Input File"
              id="inputFile"
              name="inputFile"
              required={isEdit ? false : segmentType === CSV}
              onChange={handleFileChange}
              className="form-margin"
            />
          )}
          {data.inputFileID && segmentType !== SQL && (
            <div>
              Currently uploaded file ID:{' '}
              <span className="segment-modal-description">{data.inputFileID}</span>
              <br />
              (If no file is chosen, the previous file will be used.)
            </div>
          )}
        </React.Fragment>
        <div className="footer">
          <button className="btn btn--primary">
            {isEdit ? 'Update' : 'Create'} Segment
            <span className="spin-btn" />
          </button>
        </div>
      </Form>
    </ModalContent>
  );
}

AddEditSegment.defaultProps = {
  isEdit: false,
  data: {},
};

AddEditSegment.propTypes = {
  collection: PropTypes.object.isRequired,
  isEdit: PropTypes.bool,
  data: PropTypes.object,
};
