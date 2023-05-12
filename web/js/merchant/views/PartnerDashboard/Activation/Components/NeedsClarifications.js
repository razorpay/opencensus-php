import React from 'react';
import Input from 'common/new-ui/Input';
import Form from 'common/new-ui/Form';
import { merchantFetch } from 'merchant/utils/ajax';
import { defaultFieldProps, ActivationField } from 'merchant/components/Activation';
import { prevent } from 'common/utils/rzp-utils';
import { connect } from 'react-redux';
import { showNotification } from 'merchant_common/reducers/notifications';

const NeedsClarification = (props) => {
  const {
    data,
    formState,
    NCFields,
    commentlist,
    setCommentlist,
    user,
    ncFormResponse,
    setNCFormResponse,
    ...rest
  } = props;

  const handleComment = (e, key, removecomment) => {
    prevent(e);

    const prevCommentFromState = commentlist;

    const removeCommentFromList = { ...prevCommentFromState };
    delete removeCommentFromList[key];

    const addCommentToList = { ...prevCommentFromState, [key]: e.target.value || '' };

    const comments = removecomment ? removeCommentFromList : addCommentToList;
    setCommentlist(comments);
  };

  const saveFile = (fieldName, file, progressTracker, uploadAs) => {
    const formData = new FormData();
    if (typeof uploadAs === 'string') {
      fieldName = uploadAs;
    }
    formData.append('document_type', fieldName);
    formData.append('file', file);
    return merchantFetch({
      url: 'merchant/documents/upload',
      method: 'post',
      mode: 'live',
      data: formData,
      accountId: rest.accountId,
      onUploadProgress: progressTracker,
    })
      .then((response) => {
        if (response.data) {
          props.showNotification({
            type: 'success',
            message: 'File uploaded successfully',
          });
        }
        return response;
      })
      .catch((err) => {
        if (err.errors.length && err.errors[0]) {
          props.showNotification({
            type: 'error',
            message: err.errors,
          });
        }

        return err;
      });
  };

  const ActivationFieldProps = {
    props: {
      data,
      user: {
        canSkipPoiValidation: false,
        isSyncExperimentEnabled: false,
      },
    },
    state: {
      dirty: { ...ncFormResponse },
      commentlist,
      bank_proof: ncFormResponse?.bank_proof,
    },
    isOnKYCTab: () => {
      return true;
    },
    isNeedsClarificationMode: () => {
      return true;
    },
    handleComment,
  };

  const updateFileInResponse = (filename) => {
    setNCFormResponse((prevState) => ({
      ...prevState,
      [filename]: 'fakepath',
    }));
  };

  const prepareFileFields = (a) => {
    if (a._cmp === Input.File) {
      a._cmp = Input.File;
      a._accept = ['pdf', 'image'];
      a._showAcceptInfo = false;
      a._showStagedFileStatus = false;

      if (!a.hasOwnProperty('required')) {
        a.required = true;
      }

      a.onChange = (file, progressTracker) => {
        const filename = a.getName ? a.getName(ActivationFieldProps) : a.name;

        return saveFile(filename, file, progressTracker, a.uploadAs || null).then(() => {
          updateFileInResponse(filename);
        });
      };
    }
  };

  const getNCForm = () => {
    const ncFieldsPrepared = [...NCFields];
    defaultFieldProps.call(ActivationFieldProps, ncFieldsPrepared); // Set the default props for all tab content views
    ncFieldsPrepared.forEach(prepareFileFields);
    const content = ncFieldsPrepared.map((field, i) => {
      // remove condition when to show as we want to always show the component
      if (field._when) {
        delete field._when;
      }
      if (Array.isArray(field)) {
        return (
          <Input.Group key={i}>{field.map(ActivationField, ActivationFieldProps)}</Input.Group>
        );
      }
      return ActivationField.call(ActivationFieldProps, field);
    });
    return content;
  };

  const onFormChange = (e) => {
    const { value } = e.target;
    const field = e.target.name || e.target.getAttribute('data-name');
    setNCFormResponse((state) => {
      return {
        ...state,
        [field]: value,
      };
    });
  };

  return (
    <>
      <span className="sub-text-nc">
        You can add comments in case you have any doubts or questions regarding any issue (max 200
        chars)
      </span>
      <Form onChange={onFormChange} className="Form Form--tabular">
        {getNCForm()}
      </Form>
    </>
  );
};

export default connect(
  (state) => ({
    user: state.session.user,
  }),
  { showNotification },
)(NeedsClarification);
