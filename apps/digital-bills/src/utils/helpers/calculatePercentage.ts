export const calculatePercentage = (value: number, total: number): number => {
  if (!value || !total) return 0;
  return Math.round((value / total) * 100);
};
