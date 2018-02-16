import React, { Component, Fragment } from 'react';

// TODO: temporary file upload till the file upload component is built
import FileUploadInputButton from 'rzp/ui/FileUpload/InputButton';

import ModalHeader from 'rzp/ui/ModalHeader';
import { titleCase } from 'rzp/utils/rzp-utils';

export default function UploadModal({
  closeModal,
  sampleUrl,
  docUrl,
  batchType,
  shouldLoadMore,
  notification,
  onLoadMore,
  onFileChange,
}) {
  return (
    <div class="batch-upload-modal">
      <ModalHeader title="Batch Upload" onCloseClick={closeModal} />
      <div class="modal-body">
        <h4 class="modal-heading">Upload File</h4>
        <div class="modal-file">
          <FileUploadInputButton
            accept="xlsx,csv"
            uploadedFileName="Upload File here"
            maxSize="1000000"
            onChange={onFileChange}
          />
          {notification && (
            <h5 class={`notification ${notification.status}`}>
              <i class="i i-info-circle m-r" />
              {notification.msg}
            </h5>
          )}
        </div>

        {/* Show batch upload modal info when no file uploaded */}
        {!notification && (
          <div class="modal-info">
            <h5>
              Getting Started with Batch Uploads?{' '}
              <a class="btn btn-link m-l" href={docUrl} target="_blank">
                View Documentation <i class="i i-external-link" />
              </a>
            </h5>
            <p>
              Upload a batch file to continue. Please note the following things
              before proceeding further:{' '}
              {!shouldLoadMore && (
                <span class="btn-link clickable" onClick={onLoadMore}>
                  Load More
                </span>
              )}
            </p>
            {shouldLoadMore && (
              <Fragment>
                <ul>
                  <li>1. The Amount mentioned should be in Paise.</li>
                  <li>
                    2. The receipt id for all {titleCase(batchType)} should be
                    unique.
                  </li>
                  <li>3. The number of rows should not exceed 5000.</li>
                </ul>
                <p>
                  In case of any issues, please{' '}
                  <a class="btn-link" href={sampleUrl}>
                    download sample file
                  </a>
                </p>
              </Fragment>
            )}
          </div>
        )}

        {/* Show batch modal error-info when file upload */}
        {notification && notification.status === 'error' ? (
          <div class="modal-error-info">
            <h4 class="m-b">How to fix an error?</h4>
            <div class="row m-t">
              <div class="col-sm-9">
                <p>
                  The errors are marked in a the same file in a separate column.
                  <br />
                  Download the error file, fix the errors and upload again to
                  proceed.
                </p>
              </div>
              <div class="col-sm-3">
                <button class="btn btn-primary">
                  {' '}
                  <i class="i i-download m-r" /> Download File
                </button>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
