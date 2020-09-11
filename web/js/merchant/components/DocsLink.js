import React from 'react';
import ShowWhen from './ShowWhen';

export default function DocsLink({ url, title = 'Documentation', style = {}, onClick, child }) {
  if (typeof title === 'String') {
    title = `${title}`;
  }

  return (
    <ShowWhen additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}>
      <a className="btn btn-link" href={url} target="_blank" style={style} onClick={onClick}>
        {title} &nbsp;
        <i className="i i-external-link" />
      </a>
    </ShowWhen>
  );
}
