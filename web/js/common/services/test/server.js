import { setupServer } from 'msw/node';
import { handlers } from '../../../../mocks/handlers';

const server = setupServer(...handlers);

export * from 'msw';
export { server };
