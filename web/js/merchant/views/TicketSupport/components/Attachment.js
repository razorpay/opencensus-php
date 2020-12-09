import React from 'react';
import { getFileTypeIcon } from 'common/utils/rzp-utils';

const Attachment = ({ file }) => {
  const { name, attachment_url } = file;
  const assetURL = `/dist/css/assets/files/file-type-${getFileTypeIcon(name)}.svg`;

  return (
    <a href={attachment_url} target="_blank" rel="noopener noreferrer" download={name}>
      <span className="file-attachment">
        <img src={assetURL} />
        <span>{name}</span>
      </span>
    </a>
  );
};

export default React.memo(Attachment);
