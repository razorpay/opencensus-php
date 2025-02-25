import crypto from 'crypto';
import type { Request, Response, NextFunction } from 'express';

export const requestIdMiddleware = () => {
  return (req: Request, res: Response, next: NextFunction) => {
    const requestId = crypto.randomBytes(16).toString('hex');

    req.x_shell_request_id = requestId;

    res.setHeader('x-request-id', requestId);

    return next();
  };
};
