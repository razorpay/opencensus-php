import { setupWorker } from 'msw';
import { handlers } from './msw.global.handlers';

export const browserMswWorker = setupWorker(...handlers);
