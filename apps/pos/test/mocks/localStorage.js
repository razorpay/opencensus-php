// Mock for common/utils/localStorage module
const mockLocalStorage = {
  setItem: jest.fn(),
  getItem: jest.fn(),
  removeItem: jest.fn(),
};

export default {
  __esModule: true,
  setItem: mockLocalStorage.setItem,
  getItem: mockLocalStorage.getItem,
  removeItem: mockLocalStorage.removeItem,
  default: mockLocalStorage,
};
