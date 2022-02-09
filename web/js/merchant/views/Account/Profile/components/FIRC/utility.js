export const MONTHS = [
  { label: 'January', value: '01' },
  { label: 'February', value: '02' },
  { label: 'March', value: '03' },
  { label: 'April', value: '04' },
  { label: 'May', value: '05' },
  { label: 'June', value: '06' },
  { label: 'July', value: '07' },
  { label: 'August', value: '08' },
  { label: 'September', value: '09' },
  { label: 'October', value: '10' },
  { label: 'November', value: '11' },
  { label: 'December', value: '12' },
];

export const MODAL_HEADING = {
  1: 'Select Purpose Code',
  2: 'Enter IEC Code',
  3: 'Confirmation',
};

export const SPECIAL_PURPOSE_CODES = ['P0103', 'P0807'];

export const getDataFromAPI = (response) => {
  const success = response.success;
  if (success) {
    const data = response.data;
    return data;
  } else throw new Error('FIRC API call failed');
};

export const getListOfYears = (startYear = 2021) => {
  const date = new Date();
  const currentYear = date.getFullYear();
  const years = [];
  while (startYear <= currentYear) {
    years.push(startYear++);
  }
  return years || [];
};

export const downloadFile = (response) => {
  const data = getDataFromAPI(response);
  const signed_url = data.signed_url;
  window.open(signed_url, '_blank');
};

export function inputFormatToAlpha(str = '') {
  let res = '';
  if (typeof str === 'string' && str !== '') {
    res = str.replace(/[^0-9A-Z]+/gi, '');
    res = res.toUpperCase();
  }
  return res;
}

export const computeSearch = (source, str) => {
  const res = [];

  if (str.trim() !== '') {
    str = str.toLowerCase();
    source.forEach(({ codes }) => {
      codes.forEach((code) => {
        const purposeCodeExist = code.purposeCode.toLowerCase().includes(str);
        const descriptionExist = code.description.toLowerCase().includes(str);
        if (purposeCodeExist || descriptionExist) res.push(code);
      });
    });
  }

  return res;
};
