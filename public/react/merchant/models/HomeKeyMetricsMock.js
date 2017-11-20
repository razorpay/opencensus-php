const randomize = (value, min = 0) => Math.ceil(Math.random() * value) + min;

const getPercentages = function() {
  const args = [];

  const total = Array.prototype.reduce.apply(arguments, [
    (prevValue, value) => (args.push(value), prevValue + value),
    0,
  ]);

  return args.map(value => value / total * 100);
};

const getCounts = (type = null) => {
  const values = [
    randomize(242460, 10000),
    randomize(12460, 200),
    randomize(738),
    randomize(3697),
  ];

  const percentages = getPercentages(...values);

  const counts = {
    transactionVolume: {
      value: values[0],
    },
    numTransactions: {
      value: values[1],
    },
    refunds: {
      value: values[2],
    },
    savedCards: {
      value: values[3],
    },
    transactionVolumePercentage: {
      value: percentages[0],
    },
    numTransactionsPercentage: {
      value: percentages[1],
    },
    refundsPercentage: {
      value: percentages[2],
    },
    savedCardsPercentage: {
      value: percentages[3],
    },
  };

  return type ? { [type]: counts[type] } : counts;
};

const getStep = breakDown => {
  let step = 0;

  switch (breakDown) {
    case 'daily':
      step = 1000 * 60 * 60 * 24;
      break;
    case 'weekly':
      step = getStep('daily') * 7;
      break;
    case 'monthly':
      step = getStep('weekly') * 4;
  }

  return step;
};

const getHistogram = step => {
  const now = Date.now(),
    histogram = [],
    values = [];

  for (let i = 0; i < 10; i++) {
    histogram.push({
      timestamp: now + i * step,
      value: randomize(500),
    });

    values.push(histogram[histogram.length - 1].value);
  }

  const sum = values.reduce((prev, current) => prev + current, 0);

  return { histogram, sum };
};

export const getData = (tab = null, breakDown = 'daily') => {
  const step = getStep(breakDown);

  const result = {
    diff: {
      value: (Math.random() > 0.5 ? 1 : -1) * randomize(50000, 1000),
    },
    group1: getHistogram(step),
    group2: getHistogram(step),
    group3: getHistogram(step),
    ...getCounts(tab),
  };

  return new Promise(resolve => {
    window.setTimeout(() => resolve(result), randomize(500));
  });
};
