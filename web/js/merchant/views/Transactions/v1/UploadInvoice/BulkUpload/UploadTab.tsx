import React, { useState } from 'react';

//components
import ProgressBarContinuous from 'common/components/ProgressBar/ProgressBar';
import Loader from 'common/components/Loader';

//types
import { UploadTabProps } from './types';

const UploadTab: React.FC<UploadTabProps> = ({
  tabType = 'Total',
  tabDescription,
  showLoader = false,
  progressMessage,
  progressPercent = 0,
  invoiceCount,
  errors,
}) => {
  const [isTableVisible, setIsTableVisible] = useState(true);

  //checks if error array is being passed and length is more than zero
  const isErrorPresent: boolean = Array.isArray(errors) && errors.length > 0;

  /**
   * toggles the error table on click of error files
   */
  const onStatusClick = () => {
    if (isErrorPresent) {
      setIsTableVisible(!isTableVisible);
    }
  };

  return (
    <div className="result-wrapper">
      <section className={`upload-result--tab ${tabType?.toLowerCase()}`}>
        <p className="invoice-count">{tabDescription}</p>
        {showLoader ? (
          <div className="file-progress">
            {progressMessage && <p className="progress-message">{progressMessage}</p>}
            <Loader />
          </div>
        ) : (
          <p className="status" onClick={onStatusClick}>
            {invoiceCount} Files
            {isErrorPresent && (
              <i className={`i i-chevron-right${isTableVisible ? ' open' : ''}`} />
            )}
          </p>
        )}
      </section>
      {progressPercent <= 100 && showLoader && (
        <ProgressBarContinuous percentDone={progressPercent} />
      )}
      {isErrorPresent && isTableVisible && !showLoader && (
        <section className="error-table">
          <div className="table-row head">
            <div className="table-col">File Name</div>
            <div className="table-col">Size</div>
            <div className="table-col">Error Type</div>
          </div>
          <div className="error-list">
            {errors?.map((error, index) => (
              <div className="table-row" key={index}>
                <div className="table-col">{error?.fileName}</div>
                <div className="table-col">{error?.size} KB</div>
                <div className="table-col error">{error?.message}</div>
              </div>
            ))}
          </div>
        </section>
      )}
      {isErrorPresent && !showLoader && (
        <div className="error-message">
          <i className="i i-info-outline" />
          <p className="message">
            The above files have encountered errors. Please rectify the files and Re-upload them as
            per the instructions.
          </p>
        </div>
      )}
    </div>
  );
};

export default UploadTab;
