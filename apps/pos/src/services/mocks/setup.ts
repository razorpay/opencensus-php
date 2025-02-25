import { setupServer } from 'msw-old/node';
import { handlers } from './handlers';

export const server = setupServer(...handlers);

// export * from 'msw-old';
