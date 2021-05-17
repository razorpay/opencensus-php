import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { CSSTransition } from 'react-transition-group';

import Button from 'common/new-ui/Button';
import ToggleWithDescription from 'merchant/views/Capital/components/ToggleWithDescription';
import FileUpload from 'merchant/components/File/Upload';
import { NativeUploadMeta } from './PreVerificationComponents';
import { NOOP } from 'merchant/views/Capital/Loans/constants';
import { trackToastClick, trackFilesDrop } from './ga';

const NativeUpload = ({
  selected,
  disabled,
  hasFiles,
  documentConfig,
  onClick,
  onFileChange,
  onRemoveFile,
}) => {
  const [loading, setLoading] = useState(false);
  const [messageVisible, setMessageVisibility] = useState(true);

  const handleClick = () => {
    if (disabled) return;

    onClick();
  };

  const handleToastMessageClick = (dismissed = false) => {
    trackToastClick(dismissed);
    setMessageVisibility(false);
  };

  return (
    <div className="native-upload">
      <ToggleWithDescription
        title="Upload from this Device"
        description="Manually upload your last 6 months to till day bank statement."
        meta={<NativeUploadMeta />}
        selected={selected}
        loading={loading}
        onClick={handleClick}
        style={{ marginBottom: 12 }}
        showRadioInput={true}
        radioPosition="left"
        disabled={disabled}
      />
      {selected ? (
        <FileUpload
          showCloseBtn={false}
          showFileSize
          name={documentConfig.id}
          stagedFileStatus="success"
          showAcceptInfo
          accept={documentConfig.acceptDocumentTypes}
          size="large"
          defaultValue={!!documentConfig.store_id}
          uploadedFileName="Upload File here"
          onFileChange={onFileChange}
          onCloseClick={onRemoveFile}
          onFileDrop={trackFilesDrop}
          dropZoneCavityClassName={documentConfig.id}
          removeFileButtonLabel={<i className="i i-close" />}
          id={documentConfig.id}
          multi
          showCloseBtn
          hideLoader
        />
      ) : null}
      <CSSTransition
        in={messageVisible && hasFiles}
        timeout={1000}
        classNames="native-upload__message"
        unmountOnExit
      >
        <div className="native-upload__message">
          <div>
            <p>
              Kindly ensure the uploaded file(s) has bank statements for last 6 months to till date
              before submitting.
            </p>
            <i className="i i-close" onClick={() => handleToastMessageClick(true)} />
          </div>
          <Button.Transparent onClick={() => handleToastMessageClick()}>
            UNDERSTOOD
          </Button.Transparent>
        </div>
      </CSSTransition>
    </div>
  );
};

NativeUpload.propTypes = {
  selected: PropTypes.bool,
  disabled: PropTypes.bool,
  hasFiles: PropTypes.bool,
  documentConfig: PropTypes.object,
  onClick: PropTypes.func,
  onFileChange: PropTypes.func,
  onRemoveFile: PropTypes.func,
};

NativeUpload.defaultProps = {
  selected: false,
  disabled: false,
  hasFiles: false,
  documentConfig: {},
  onFileChange: NOOP,
  onRemoveFile: NOOP,
};

export default NativeUpload;
