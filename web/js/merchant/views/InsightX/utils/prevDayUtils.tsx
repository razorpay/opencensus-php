export const getPrevDayTimestamps = () => {
  const today = new Date();
  const startOfPrevDay = new Date(today.setDate(today.getDate() - 1));
  startOfPrevDay.setHours(0, 0, 0, 0);
  const endOfPrevDay = new Date(startOfPrevDay);
  endOfPrevDay.setHours(23, 59, 59, 999);
  return {
    startOfPrevDay: Math.floor(startOfPrevDay.getTime() / 1000),
    endOfPrevDay: Math.floor(endOfPrevDay.getTime() / 1000),
  };
};
