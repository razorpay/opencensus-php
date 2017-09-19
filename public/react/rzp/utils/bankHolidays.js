const holidays = {
  '2017': {
    '9': {
      '2': 'Bakri Id (Id-ul-Zuha)',
      '23': 'sa',
      '24': 'su',
      '30': 'Durga Puja/Dussehra (Vijaya Dashmi)',
    },
    '10': {
      '1': 'su',
      '2': 'Mahatma Gandhi Jayanti',
      '8': 'su',
      '14': 'sa',
      '15': 'su',
      '19': 'Diwali Amavasaya (Laxmi Pujan)/Kali Puja',
      '20': 'Diwali (Balipratipada)',
      '22': 'su',
      '28': 'sa',
      '29': 'su',
    },
    '11': {
      '4': 'Guru Nanak Jayanti',
      '5': 'su',
      '11': 'sa',
      '12': 'su',
      '19': 'su',
      '25': 'sa',
      '26': 'su',
    },
    '12': {
      '1': 'Id-e-Milad/Eid Milad-un-Nabi',
      '3': 'su',
      '9': 'sa',
      '10': 'su',
      '17': 'su',
      '23': 'sa',
      '24': 'su',
      '25': 'Christmas',
      '31': 'su',
    },
  },
};

export const isHoliday = date => {
  if (!(date instanceof Date)) {
    return false;
  }

  const year = date.getFullYear();

  if (!holidays[year]) {
    return false;
  }

  const month = date.getMonth() + 1;

  if (!holidays[year][month]) {
    return false;
  }

  const day = date.getDate(),
    reason = holidays[year][month][day];

  if (!reason) {
    return false;
  }

  if (reason === 'sa') {
    return 'Second Saturday';
  } else if (reason === 'su') {
    return 'Sunday';
  }

  return reason;
};
