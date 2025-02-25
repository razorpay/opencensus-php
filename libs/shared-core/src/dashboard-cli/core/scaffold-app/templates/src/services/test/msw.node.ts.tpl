import { setupServer } from 'msw/node';
import { handlers } from './msw.global.handlers';

export const nodeMswServer = setupServer(...handlers);
