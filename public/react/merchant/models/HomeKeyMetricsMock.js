const randomize = (value, min = 0) => Math.ceil(Math.random() * value) + min;

const getCounts = (type = null) => {
  const counts = {
    transactionVolume: {
      value: randomize(242460, 10000),
    },
    numTransactions: {
      value: randomize(12460, 200),
    },
    refunds: {
      value: randomize(738),
    },
    savedCards: {
      value: randomize(3697),
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

export const getData = (tab = null, breakDown = 'daily') => {
  const step = getStep(breakDown),
    histogram = [],
    now = Date.now();

  for (let i = 0; i < 10; i++) {
    histogram.push({
      timestamp: now + i * step,
      value: randomize(500),
    });
  }

  const result = {
    diff: {
      value: (Math.random() > 0.5 ? 1 : -1) * randomize(1000, 20),
    },
    histogram: histogram,
    ...getCounts(tab),
  };

  return new Promise(resolve => {
    window.setTimeout(() => resolve(result), randomize(5000, 200));
  });
};
