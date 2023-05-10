export const apiCall = () => {
  return new Promise<{ json: () => string[] }>((res) =>
    res({
      json: () => ['TEST1', 'TEST2'],
    }),
  ).then((x) => x.json());
};
