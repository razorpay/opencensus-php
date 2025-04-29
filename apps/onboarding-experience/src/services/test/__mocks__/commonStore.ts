export const useStore = jest.fn().mockImplementation(() => ({
  session: {
    user: {
      id: 'test-user-id',
      merchant: { id: 'test-merchant-id' },
      websites: [],
    },
  },
}));
