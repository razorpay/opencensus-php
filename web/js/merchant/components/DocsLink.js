import React from 'react';
import ShowWhen from './ShowWhen';
import { getUser } from 'merchant/store';
import { HIDDEN_INTERNATIONAL_FEATURES_TAGS } from 'merchant/constants/tags';

export default function DocsLink({ url, title = 'Documentation', style = {}, onClick }) {
  if (typeof title === 'string') {
    title = `${title}`;
  }

  const modifiedURL = getCustomURL(url);
  return (
    <ShowWhen
      additionalCondition={(user) =>
        user.isOrgAllowedFunctionality('external_links') &&
        !user.findTag(HIDDEN_INTERNATIONAL_FEATURES_TAGS.Documentation)
      }
    >
      <a
        className="btn btn-link"
        href={modifiedURL}
        target="_blank"
        style={style}
        onClick={onClick}
        rel="noreferrer noopener"
      >
        {title} &nbsp;
        <i className="i i-external-link" />
      </a>
    </ShowWhen>
  );
}

const ORG_TO_URL_MAPPING = {
  curlec: 'curlec.com',
  rzp: 'razorpay.com',
};

/** Docs URL:
 * https://razorpay.com/docs/invoices/
 * https://axisbank-docs.razorpay.com/invoices/
 * https://${org}-docs.razorpay.com/${path}
 **/

export function getCustomURL(url) {
  const user = getUser();
  const orgCustomCode = user.orgCustomCode;

  if (user.isOrgRZP) return url;
  if (user.isOrgCurlec) {
    return url ? url.replace(ORG_TO_URL_MAPPING.rzp, ORG_TO_URL_MAPPING[orgCustomCode]) : url;
  }

  const urlSplits = url?.split('://');
  const org = orgCustomCode === 'axis' ? 'axisbank' : orgCustomCode;
  const link =
    orgCustomCode !== 'axis' ? url : `https://${org}-docs.${urlSplits[1]}`.replace('/docs', '');

  return link;
}

export const DocLink = (props) => {
  const { href, target = '_blank' } = props;
  return (
    <a {...props} href={getCustomURL(href)} rel="noreferrer noopener" target={target}>
      {props.children}
    </a>
  );
};
