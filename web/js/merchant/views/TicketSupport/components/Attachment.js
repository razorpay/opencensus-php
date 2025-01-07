import React from 'react';
import { getFileTypeIcon } from 'common/utils/rzp-utils';

const Attachment = ({ file }) => {
  const { name, attachment_url } = file;
  return (
    <a href={attachment_url} target="_blank" rel="noopener noreferrer" download={name}>
      <span className="file-attachment">
        <img src={require(`assets/files/file-type-${getFileTypeIcon(name)}.svg`)} />
        <span>{name}</span>
      </span>
    </a>
  );
};

export default React.memo(Attachment);
