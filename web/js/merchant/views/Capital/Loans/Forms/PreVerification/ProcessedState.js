import React, { useEffect } from 'react';
import PropTypes from 'prop-types';

import { AVAILABLE_FILE_TYPE_ICONS } from 'merchant/views/Capital/Loans/constants';
import { trackPreverificationCompleteLoad } from './ga';

const getFileTypeIcon = (fileName) => {
  let fileType = fileName.split('.');
  fileType = fileType[fileType.length - 1];

  return AVAILABLE_FILE_TYPE_ICONS.indexOf(fileType) > -1 ? fileType : 'misc';
};

const ProcessedState = ({ isNetbankingUpload, files }) => {
  useEffect(trackPreverificationCompleteLoad, []);

  const description = isNetbankingUpload
    ? 'We’ve received your last 6 months bank statements via Perfios.'
    : files && files.length
    ? 'We’ve received the following bank statements from you.'
    : 'We’ve received your last 6 months bank statements.';

  return (
    <div className="processed-state">
      <div className="toggle-with-description processed-state__message">
        <i className="i i-check"></i>
        {description}
      </div>
      {isNetbankingUpload ? null : (
        <div className="processed-state__files">
          {files.map(({ id, name = '', file_type = '' }) => {
            return (
              <div className="toggle-with-description" key={id}>
                <img
                  src={require(`assets/files/file-type-${getFileTypeIcon(
                    name || file_type || '',
                  )}.svg`)}
                  alt={name || 'Bank Statement'}
                />
                {`${name || 'Bank Statement.pdf'}`}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
};

ProcessedState.propTypes = {
  files: PropTypes.array,
  isNetbankingUpload: PropTypes.bool,
};

ProcessedState.defaultProps = {
  files: [],
  isNetbankingUpload: true,
};

export default ProcessedState;
