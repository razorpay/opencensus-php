import { Request } from 'express';
import { getCookies } from './getCookies';

export const getMandatoryHeaders = (req: Request) => {
  return {
    Cookie: getCookies(req),
    'rzpctx-dev-serve-user': req?.headers?.['rzpctx-dev-serve-user'],
    "kong-debug": req?.headers?.["kong-debug"],
  } as HeadersInit;
};
