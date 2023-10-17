export const cachedData = {
  2021: {
    January: 'January',
    February: 'February',
  },
  2022: {
    March: 'March',
  },
};

export const firsData = {
  year: 2023,
  months: {
    January: ['some data'],
    November: ['some data'],
  },
};

export const mockGlobalDate = (date) => {
  const mockDate = new Date(date);
  const originalDate = global.Date;
  global.Date = jest.fn(() => mockDate);

  const onComplete = () => {
    global.Date = originalDate;
  };
  return { onComplete, mockDate };
};
