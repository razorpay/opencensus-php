import '@testing-library/jest-dom/extend-expect';
import 'regenerator-runtime/runtime';
import { queryCache } from '../../components/Bootstrap/Wrapper';
import { server } from '../../../../mocks/node';
process.env.hostName = 'http://localhost:6006';

jest.mock('merchant/utils/ajax');

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterAll(() => server.close());
beforeEach(() => server.resetHandlers());

if (!process.env.LISTENING_TO_UNHANDLED_REJECTION) {
  process.on('unhandledRejection', (reason) => {
    throw reason;
  });

  // Avoid memory leak by adding too many listeners
  process.env.LISTENING_TO_UNHANDLED_REJECTION = true;
}

afterEach(() => {
  queryCache.clear();
});
