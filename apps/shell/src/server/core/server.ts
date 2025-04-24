import { createServer as createHttpServer } from 'http';
import { createServer as createHttpsServer } from 'https';
import { STAGE } from '@apps/shell/src/env';
import { Application } from 'express';
import fs from 'fs';
import path from 'path';

export const mainServer = (app: Application) => {
  const isDev = STAGE === 'development';

  if (isDev) {
    const sslOptions = {
      key: fs.readFileSync(path.resolve(process.cwd(), 'certs/key.pem')),
      cert: fs.readFileSync(path.resolve(process.cwd(), 'certs/cert.pem')),
    };
    return createHttpsServer(sslOptions, app);
  } else {
    return createHttpServer(app);
  }
};
