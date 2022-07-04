import moment from 'moment';

const momentDurationMap = {
  minute: 'minutes',
  hour: 'hours',
  day: 'days',
};

function getLabel(aggregation, time) {
  let label;
  if (aggregation.value === 'day') {
    label = moment(Number(time)).startOf('day');
  } else if (aggregation.value === 'hour') {
    label = moment(Number(time)).startOf('hour');
  } else {
    label = moment(Number(time));
  }
  return label;
}

export function getChartData(data, aggregation, duration, filteredStatusCodeList) {
  return (canvas) => {
    const statDataMap = {
      '2xx': [],
      '3xx': [],
      '4xx': [],
      '5xx': [],
      all: [],
    };

    const timestampToStatMap = {};
    const { stats } = data;

    stats.forEach(({ timestamp }) => {
      const timestampInMillis = timestamp * 1000;
      timestampToStatMap[timestampInMillis] = [];
    });

    const timestamps = Object.keys(timestampToStatMap).sort((a, b) => {
      return Number(a) - Number(b);
    });

    /**
     * timestamps = ["02/08", "04/08", "05/08", "07/08"]
     * timestampsWithGapsFilled = ["01/08", "02/08", "03/08", "04/08", "05/08", "06/08", "07/08"]
     */
    const timestampsWithGapsFilled = fillTimestamps(
      timestamps,
      aggregation.value,
      duration,
      timestampToStatMap,
    );

    stats.forEach(({ timestamp, dimension, count }) => {
      const timestampInMillis = timestamp * 1000;
      timestampToStatMap[timestampInMillis].push({ dimension, count });
    });

    const labelToDataMap = {};

    timestampsWithGapsFilled.forEach((timestamp) => {
      const label = getLabel(aggregation, timestamp);
      const dataForLabel = labelToDataMap[label] || {
        '2xx': 0,
        '3xx': 0,
        '4xx': 0,
        '5xx': 0,
        all: 0,
      };

      const updatedDataForLabel = computeLabelDataForTimestamp(
        timestampToStatMap,
        timestamp,
        dataForLabel,
      );

      labelToDataMap[label] = updatedDataForLabel;
    });

    const labels = Object.keys(labelToDataMap);

    Object.values(labelToDataMap).forEach((statData) => {
      Object.keys(statData).forEach((statKey) => {
        statDataMap[statKey].push(statData[statKey]);
      });
    });

    const ctx = canvas.getContext('2d');
    const gradientFillAll = ctx.createLinearGradient(0, 0, 0, 250);
    gradientFillAll.addColorStop(0, 'rgba(37, 41, 57, 1)');
    gradientFillAll.addColorStop(0.5, 'rgba(37, 41, 57, 0)');

    const gradientFill2xx = ctx.createLinearGradient(0, 0, 0, 250);
    gradientFillAll.addColorStop(0, 'rgba(37, 41, 57, 1)');
    gradientFillAll.addColorStop(0.5, 'rgba(37, 41, 57, 0)');

    const gradientFill3xx = ctx.createLinearGradient(0, 0, 0, 250);
    gradientFillAll.addColorStop(0, 'rgba(37, 41, 57, 1)');
    gradientFillAll.addColorStop(0.5, 'rgba(37, 41, 57, 0)');

    const gradientFill4xx = ctx.createLinearGradient(0, 0, 0, 250);
    gradientFillAll.addColorStop(0, 'rgba(37, 41, 57, 1)');
    gradientFillAll.addColorStop(0.5, 'rgba(37, 41, 57, 0)');

    const gradientFill5xx = ctx.createLinearGradient(0, 0, 0, 250);
    gradientFillAll.addColorStop(0, 'rgba(37, 41, 57, 1)');
    gradientFillAll.addColorStop(0.5, 'rgba(37, 41, 57, 0)');

    const datasets = [
      {
        label: '2xx',
        data: statDataMap['2xx'],
        fill: false,
        backgroundColor: gradientFill2xx,
        borderColor: '#01B358',
        borderWidth: 1,
      },
      {
        label: '3xx',
        data: statDataMap['3xx'],
        fill: false,
        backgroundColor: gradientFill3xx,
        borderColor: '#5bc0de',
        borderWidth: 1,
      },
      {
        label: '4xx',
        data: statDataMap['4xx'],
        fill: false,
        backgroundColor: gradientFill4xx,
        borderColor: '#F9B154',
        borderWidth: 1,
      },
      {
        label: '5xx',
        data: statDataMap['5xx'],
        fill: false,
        backgroundColor: gradientFill5xx,
        borderColor: '#F54A2A',
        borderWidth: 1,
      },
      {
        label: 'all',
        data: statDataMap.all,
        fill: false,
        backgroundColor: gradientFillAll,
        borderColor: '#252939',
        borderWidth: 1,
      },
    ];

    return {
      labels,
      datasets: filteredStatusCodeList.length
        ? filteredDatasets(datasets, filteredStatusCodeList)
        : datasets,
    };
  };
}

function fillTimestamps(timestamps, aggregation, duration, timestampToStatMap) {
  let filledTimestamps = [...timestamps];
  const { from, to } = duration;
  const firstTimestamp = timestamps[0];
  const lastTimestamp = timestamps[timestamps.length - 1];

  if (aggregation === 'hour') {
    const firstHour = moment(Number(firstTimestamp)).startOf('hour').toDate(); // from response
    const lastHour = moment(Number(lastTimestamp)).startOf('hour').toDate();

    const startMs = moment(Number(from)).startOf('hour').toDate(); // from filters
    const endMs = moment().isSame(Number(to), 'day')
      ? moment().startOf('hour').toDate()
      : moment(Number(to)).startOf('hour').add(1, 'hours').toDate();

    if (firstHour > startMs) {
      filledTimestamps.unshift(startMs.getTime());
    }

    if (lastHour < endMs) {
      filledTimestamps.push(endMs.getTime());
    }
  } else if (aggregation === 'minute') {
    // 15 minute aggregation
    const first15Mins = roundUp15Minutes(Number(firstTimestamp)).toDate(); // from response
    const last15Mins = roundDown15Minutes(Number(lastTimestamp)).toDate();

    const startMs = roundUp15Minutes(Number(from)).toDate();
    const endMs = roundDown15Minutes(Number(to)).toDate();

    if (first15Mins > startMs) {
      filledTimestamps.unshift(startMs.getTime());
    }

    if (last15Mins < endMs) {
      filledTimestamps.push(endMs.getTime());
    }
  } else {
    const firstDayStart = moment(Number(firstTimestamp)).startOf('day').toDate();
    const lastDayStart = moment(Number(lastTimestamp)).startOf('day').toDate();

    const startMs = moment(Number(from)).startOf('day').toDate();
    const endMs = moment(Number(to)).startOf('day').toDate();

    if (firstDayStart > startMs) {
      filledTimestamps.unshift(startMs.getTime());
    }

    if (lastDayStart < endMs) {
      filledTimestamps.push(endMs.getTime());
    }
  }

  filledTimestamps = filledTimestamps.reduce((result, timestamp) => {
    /* fill in the missing data points*/

    timestamp = Number(timestamp);
    const prevTimestamp = result[result.length - 1];

    if (!prevTimestamp) {
      result.push(timestamp);
      return result;
    }

    const intermediateTimestamps = getTimestampsWithGapFilled(
      timestamp,
      prevTimestamp,
      aggregation,
    );

    result = result.concat(intermediateTimestamps);
    result.push(timestamp);

    return result;
  }, []);

  filledTimestamps.forEach((timestamp) => {
    timestampToStatMap[timestamp] = [];
  });
  return filledTimestamps;
}

function filteredDatasets(datasets, filteredStatusCodeList) {
  return datasets.filter((dataset) => !filteredStatusCodeList.includes(dataset.label));
}

function roundUp15Minutes(time) {
  const minutes = moment(time).minutes();
  if (minutes >= 0 && minutes < 15) {
    return moment(time).minutes(0).seconds(0);
  } else if (minutes >= 15 && minutes < 30) {
    return moment(time).minutes(15).seconds(0);
  } else if (minutes >= 30 && minutes < 45) {
    return moment(time).minutes(30).seconds(0);
  } else if (minutes >= 45 && minutes < 60) {
    return moment(time).minutes(45).seconds(0);
  } else {
    return moment(time).seconds(0);
  }
}

function roundDown15Minutes(time) {
  const minutes = moment(time).minutes();
  if (minutes > 0 && minutes <= 15) {
    return moment(time).minutes(15).seconds(0);
  } else if (minutes > 15 && minutes <= 30) {
    return moment(time).minutes(30).seconds(0);
  } else if (minutes > 30 && minutes <= 45) {
    return moment(time).minutes(45).seconds(0);
  } else if (minutes > 45 && minutes <= 60) {
    return moment(time).minutes(60).seconds(0);
  } else {
    return moment(time).seconds(0);
  }
}

function getTimestampsWithGapFilled(currTimestamp, prevTimestamp, aggregation) {
  const timestamps = [];
  let timestamp = prevTimestamp;
  let numPointsGap = moment(currTimestamp).diff(prevTimestamp, momentDurationMap[aggregation]);
  let addValue = 1;
  if (aggregation === 'minute') {
    numPointsGap = Math.floor(numPointsGap / 15);
    addValue = 15;
  }
  while (numPointsGap > 1) {
    timestamp = moment(timestamp).add(addValue, momentDurationMap[aggregation]).toDate().getTime();
    timestamps.push(timestamp);
    numPointsGap--;
  }
  return timestamps;
}

function computeLabelDataForTimestamp(timestampToStatMap, timestamp, dataForLabel) {
  /**
   * timestampToStatMap[timestamp] = [
   *  {
   *    {
   *      http_status_code: 201
   *    },
   *    count: 4
   *  },
   *  {
   *    {
   *      http_status_code: 301
   *    },
   *    count: 4
   *  },
   * {
   *    {
   *      http_status_code: 400
   *    },
   *    count: 2
   *  },{
   *    {
   *      http_status_code: 503
   *    },
   *    count: 1
   *  },
   * ]
   */

  const updatedDataForLabel = { ...dataForLabel };

  timestampToStatMap[timestamp].forEach(({ dimension: { http_status_code }, count }) => {
    updatedDataForLabel.all += count;
    if (http_status_code < 300) {
      updatedDataForLabel['2xx'] += count;
    } else if (http_status_code < 400) {
      updatedDataForLabel['3xx'] += count;
    } else if (http_status_code < 500) {
      updatedDataForLabel['4xx'] += count;
    } else {
      updatedDataForLabel['5xx'] += count;
    }
  });
  return updatedDataForLabel;
}
