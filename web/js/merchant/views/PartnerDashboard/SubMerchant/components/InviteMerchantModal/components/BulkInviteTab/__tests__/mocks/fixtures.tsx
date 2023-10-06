import React, { useState } from 'react';

export const MockBatchValidate = ({
  validateBatch,
  onValidation,
  onValidationFail,
  clickToUploadAnalytics = () => {},
  sampleUrl,
}) => {
  const [isLoading, setIsLoading] = useState(false);
  const [validationError, setValidationError] = useState('');
  return (
    <div>
      <input
        type="file"
        data-testid="upload-input"
        onChange={() => {
          setIsLoading(true);
          validateBatch()
            .then((response) => {
              setIsLoading(false);
              onValidation(response.data, 'uploadFile');
              return response.data;
            })
            .catch((error) => {
              setIsLoading(false);
              const errorMsg = error.errors[0] ?? '';
              setValidationError(errorMsg);
              if (onValidationFail) onValidationFail(errorMsg);
              return error;
            });
          clickToUploadAnalytics();
        }}
      />
      <div>
        {/* from instructions */}
        <a href={sampleUrl}>
          <strong>sample file</strong>
        </a>
        {/* Mimic validation errors */}
        <div>{isLoading ? 'Dummy Loading' : validationError}</div>
      </div>
    </div>
  );
};
export const MockBatchValidateSimple = ({
  validateBatch,
  onValidation,
  clickToUploadAnalytics,
  sampleUrl,
}) => (
  <div>
    <input
      type="file"
      data-testid="upload-input"
      onChange={() => {
        validateBatch();
        onValidation({ file_id: 'files1234', processable_count: 2 }, 'uploadFile');
        clickToUploadAnalytics();
      }}
    />
    <div>
      {/* from top link */}
      <a href={sampleUrl}>
        <strong>Download sample file</strong>
      </a>
    </div>
  </div>
);
