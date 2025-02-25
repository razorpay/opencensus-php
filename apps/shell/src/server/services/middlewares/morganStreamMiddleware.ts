import morgan from 'morgan';
import { shellLogger } from '../../utils';
import { RequestHandler } from 'express';
import { type ParamsDictionary } from 'express-serve-static-core';
import { type Request } from 'express';
import { PHP_BASE_URL, STAGE } from '@apps/shell/src/env';

const ignoreStreamFromEndpoints = ['/app-health', '/metrics'];

const morganStream = {
  write: (message: string) => {
    shellLogger.info({
      message: message.trim(),
      moduleName: '@morganStreamMiddlware',
    });
  },
};

// Define a custom token to access res.locals
morgan.token('x_shell_request_id', (req: Request) => {
  // Cast res to Response to access locals
  return req.x_shell_request_id || 'UNKNOWN';
});

export const morganStreamMiddleware = () => {
  const isDev = STAGE === 'development';

  return morgan(
    `:method ${isDev ? PHP_BASE_URL : ''}:url :status :res[content-length] - :response-time ms${
      isDev ? '' : ' - x-request-id: :x_shell_request_id'
    }`,
    {
      stream: morganStream,
      skip: (req) => ignoreStreamFromEndpoints.includes(req.path),
    },
  ) as RequestHandler<ParamsDictionary>;
};
