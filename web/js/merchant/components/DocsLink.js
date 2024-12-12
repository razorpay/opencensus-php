import React from 'react';

import ShowWhen from './ShowWhen';
import { getUser } from 'merchant/store';
import { useI18Service } from 'common/i18';
import { ExternalLinkIcon, Link } from '@razorpay/blade/components';
import { docsUrl, docsUrlTabs } from 'common/utils/constants';

export default function DocsLink({
  url,
  title = 'Documentation',
  style = {},
  onClick,
  isTab = false,
  shouldUseBladeLink = false,
  shouldApplyLineHeight = false,
  shouldFloatRight = false,
}) {
  const { isConfigTagEnabled } = useI18Service();
  if (typeof title === 'string') {
    title = `${title}`;
  }

  if (isTab) {
    url = window.location.pathname.replace('/app', '');
  }

  if (shouldApplyLineHeight) {
    style = { ...style, lineHeight: '2rem' };
  }

  if (shouldFloatRight) {
    style = { ...style, float: 'right' };
  }

  const modifiedURL = getCustomURL(url, isTab);

  if (!modifiedURL && isTab) {
    return null;
  }

  return (
    <ShowWhen
      additionalCondition={(user) =>
        user.isOrgAllowedFunctionality('external_links') &&
        !isConfigTagEnabled('documentation.documentation')
      }
    >
      {!shouldUseBladeLink ? (
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
      ) : (
        <Link
          icon={ExternalLinkIcon}
          onClick={onClick}
          target="_blank"
          iconPosition="right"
          href={modifiedURL}
        >
          {title}
        </Link>
      )}
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

export function getCustomURL(url, isTab = false) {
  const user = getUser();
  const orgCustomCode = user.orgCustomCode;

  if (user.isOrgRZP) {
    return isURL(url) ? url : isTab ? docsUrlTabs[url] : docsUrl[url];
  }

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

function isURL(str) {
  try {
    new URL(str);
    return true;
  } catch (e) {
    return false;
  }
}
