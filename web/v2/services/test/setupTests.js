import '@testing-library/jest-dom/extend-expect';
import { queryCache } from '../../components/Bootstrap/Wrapper';
import { server } from '../../../mocks/node';
process.env.hostName = 'http://localhost:6006';

beforeAll(() => server.listen({ onUnhandledRequest: 'error' }));
afterAll(() => server.close());
beforeEach(() => server.resetHandlers());

afterEach(() => {
  queryCache.clear();
});
