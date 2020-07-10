const periodMap = {
  daily: ['day', 'days'],
  monthly: ['month', 'months'],
  yearly: ['year', 'years'],
  weekly: ['week', 'weeks'],
};

export function getPeriodLabel(period, interval) {
  const isSingleInterval = interval == 1;

  if (isSingleInterval) {
    return period;
  }

  return `every ${interval} ${periodMap[period][1]}`;
}
