import { setupServer } from 'msw-old/node';
import { handlers } from './handlers';

const server = setupServer(...handlers);

export * from 'msw-old';
export { server };
