import React from 'react';
import ShowWhen from './ShowWhen';
import { getUser } from 'merchant/store';

export default function DocsLink({ url, title = 'Documentation', style = {}, onClick }) {
  if (typeof title === 'string') {
    title = `${title}`;
  }

  const modifiedURL = getCustomURL(url);
  return (
    <ShowWhen additionalCondition={(user) => user.isOrgAllowedFunctionality('external_links')}>
      <a
        className="btn btn-link"
        href={modifiedURL}
        target="_blank"
        style={style}
        onClick={onClick}
        rel="noreferrer"
      >
        {title} &nbsp;
        <i className="i i-external-link" />
      </a>
    </ShowWhen>
  );
}

/** Docs URL:
 * https://razorpay.com/docs/invoices/
 * https://axisbank-docs.razorpay.com/invoices/
 * https://${org}-docs.razorpay.com/${path}
 **/

export function getCustomURL(url) {
  const user = getUser();
  if (!user.isWhiteLabelledOrg) return url;

  const urlSplits = url.split('://');
  const org = user.orgCustomCode === 'axis' ? 'axisbank' : user.orgCustomCode;
  const link =
    user.orgCustomCode !== 'axis'
      ? url
      : `https://${org}-docs.${urlSplits[1]}`.replace('/docs', '');

  return link;
}

export const DocLink = (props) => (
  <a {...props} href={getCustomURL(props.href)}>
    {props.children}
  </a>
);
