import { Request } from 'express';
import url from 'url';
import { BANKING_SERVICE_URL, BANK_LMS_BANKING_SERVICE_URL } from '@apps/shell/src/env';

export { isBrowser } from './isBrowser';
export { getCookies } from './getCookies';
export { shellLogger } from './shellLogger';
export { getMandatoryHeaders } from './getMandatoryHeaders';
export { redirectToPhp } from './redirectToPhp';
export { getPhpBaseUrl } from './getPhpBaseUrl';

export const getRequestOriginUrl = (req: Request) => {
  return req.headers.origin || req.headers.referer || '';
};

export const isBankingOriginRequest = (req: Request) => {
  const originDomain = getRequestOriginUrl(req);
  const originHost = url.parse(originDomain).hostname;
  const bankingHost = url.parse(BANKING_SERVICE_URL || '').hostname;
  const bankLmsBankingHost = url.parse(BANK_LMS_BANKING_SERVICE_URL || '').hostname;

  return originHost === bankingHost || originHost === bankLmsBankingHost;
};
