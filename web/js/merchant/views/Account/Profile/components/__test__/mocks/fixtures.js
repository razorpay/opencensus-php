jest.mock('merchant/components/DetailRow', () => ({
  __esModule: true,
  default: ({ label, value }) => (
    <div>
      <div>{label()}</div>
      <div>{value()}</div>
    </div>
  ),
}));
