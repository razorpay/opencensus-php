import { shellLogger } from '@apps/shell/src/server/utils';

declare global {
  namespace Express {
    interface Request {
      shellLogger: typeof shellLogger;
      x_shell_request_id: string;
    }

    interface Response {
      locals: {
        destination?: string;
        destination_url?: string;
      };
    }
  }
}
