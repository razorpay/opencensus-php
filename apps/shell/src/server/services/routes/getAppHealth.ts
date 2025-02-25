import { Request, Response } from 'express';
import { renderHTML } from '../generator';

type AppVersionsRouteFn = (req: Request, res: Response) => void;

export const getAppHealth: AppVersionsRouteFn = (_, res) => {
  return res.status(200).send(
    renderHTML({
      body: 'Shell is running fine! Peace.',
    }),
  );
};
