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
    reversals: {
      value: values[2],
    },
    savedCards: {
      value: values[3],
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

  const sum = { value: values.reduce((prev, current) => prev + current, 0) };

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

  const percentages = getPercentages(
    result.group1.sum.value,
    result.group2.sum.value,
    result.group3.sum.value
  );

  result.group1.percentage = { value: percentages[0].toFixed(2) };
  result.group2.percentage = { value: percentages[1].toFixed(2) };
  result.group3.percentage = { value: percentages[2].toFixed(2) };

  return new Promise(resolve => {
    window.setTimeout(() => resolve(result), randomize(500));
  });
};

export const getTraffic = () => {
  const getValues = () => {
    const values = [
        randomize(500),
        randomize(500),
        randomize(500),
        randomize(500),
      ],
      percentages = getPercentages(...values);

    return { values, percentages };
  };

  const platforms = ['Android', 'iOS', 'mWeb', 'Desktop'];

  var { values, percentages } = getValues();

  const transactionVolumeCount = platforms.map((platform, index) => {
    return {
      platform,
      value: values[index],
    };
  });

  const transactionVolumePercentage = platforms.map((platform, index) => {
    return {
      platform,
      value: percentages[index].toFixed(2),
    };
  });

  var { values, percentages } = getValues();

  const noTransactionsCount = platforms.map((platform, index) => {
    return {
      platform,
      value: values[index],
    };
  });

  const noTransactionsPercentage = platforms.map((platform, index) => {
    return {
      platform,
      value: percentages[index].toFixed(2),
    };
  });

  return new Promise(resolve => {
    return window.setTimeout(() => {
      resolve({
        transactionVolumeCount,
        transactionVolumePercentage,
        noTransactionsCount,
        noTransactionsPercentage,
      });
    }, randomize(500));
  });
};
