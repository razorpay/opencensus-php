import FileSaver from 'file-saver';
import xlsx from 'xlsx';
import { COUNTRY_CODES } from 'common/components/CountryCodeInput/constant';
import { getAllCountries } from '@razorpay/i18nify-js';

import {
  convertToLocale,
  exportFileAsExcel,
  getCurrencyConfig,
  getCurrentFinancialYear,
  getFormattedAmount,
  i18CurrencyConversionFromCommonUnitToMinorUnit,
  i18CurrencyConversionFromMinorUnitToCommonUnit,
  isExperimentActive,
  mergeCurrencyFormatting,
  openTicketModal,
  stringTemplate,
  getAmountFieldPlaceholder,
  isConfigTagAPISupported,
  getDialCodeFromCountryCode,
  getCountryCodes,
  autoPrefixUrls,
  humanize,
} from 'common/utils/rzp-utils';
import abExperimentsMap from 'merchant/utils/abExperimentsMap';

import {
  currencyList,
  getSplitzExperiments,
  ZERO_EXPONENT_CURRENCIES,
  TWO_EXPONENT_CURRENCIES,
  THREE_EXPONENT_CURRENCIES,
} from './mocks/fixtures';

const saveAsSpy = jest.spyOn(FileSaver, 'saveAs');
const writeSpy = jest.spyOn(xlsx, 'write');
jest.mock('@razorpay/i18nify-js');

// Set navigator languages based on currency for testing formatNumber from i18nify
const langGetter = jest.spyOn(window.navigator, 'languages', 'get');

describe('test for getCurrentFinancialYear', () => {
  it('should return correct financial year for 31st march', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 2, 31));
    const currentYear = getCurrentFinancialYear();
    expect(currentYear).toBe(2022);
    jest.useRealTimers();
  });

  it('should return correct financial year for 1st april', () => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(new Date(2023, 3, 1));
    const nextYear = getCurrentFinancialYear();
    expect(nextYear).toBe(2023);
    jest.useRealTimers();
  });
});

test('stringTemplate', () => {
  const str = '/notes/{category}?noteId={noteId}';
  const replacer = { category: 'development', noteId: '1' };

  expect(stringTemplate(str, replacer)).toBe('/notes/development?noteId=1');
});

describe('Tests for currency formatting', () => {
  beforeAll(() => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
    };
  });

  test('getCurrencyConfig should refer to INR when no currency is passed', () => {
    langGetter.mockReturnValue(['en-IN', 'en']);
    const { decimals, formatter } = getCurrencyConfig();
    expect(decimals).toBe(2);
    expect(formatter('1234567.12')).toBe('12,34,567.12');
  });

  test('getCurrencyConfig should refer to local currency list when window.currencyList is undefined', () => {
    const { decimals } = getCurrencyConfig('KWD');
    expect(decimals).toBe(3);
  });

  test('getCurrencyConfig should return decimals as 2 and default formatting if Currency is not found in the list', () => {
    langGetter.mockReturnValue(['en-US', 'en']);
    const { decimals, formatter } = getCurrencyConfig('ABC');
    expect(decimals).toBe(2);
    expect(formatter(1234567.12)).toBe('1,234,567.12');
  });

  test('getCurrencyConfig should return default formatting function if formatting is not present for a currency', () => {
    const { formatter } = getCurrencyConfig('USD');
    expect(formatter(1000000.12)).toBe('1,000,000.12');
  });

  test('getCurrencyConfig should return specific formatting function if formatting is present for a currency', () => {
    langGetter.mockReturnValue(['en-IN', 'en']);
    const { formatter } = getCurrencyConfig('INR');
    expect(formatter(1000000.12)).toBe('10,00,000.12');
  });

  test('getFormattedAmount should return value with correct formatting if only amount is passed', () => {
    expect(getFormattedAmount(11111)).toBe('111.11');
  });

  test('getFormattedAmount should return value with correct formatting if amount and currency is passed', () => {
    expect(getFormattedAmount(11111, 'KWD')).toBe('11.111');
  });

  test('getFormattedAmount should return value with correct formatting if currency does not exist in the list', () => {
    expect(getFormattedAmount(11111, 'ABC')).toBe('111.11');
  });

  test('getFormattedAmount should return value with correct formatting for INR if number with length > 7 is passed', () => {
    expect(getFormattedAmount(1111111)).toBe('11,111.11');
  });

  test('getFormattedAmount should return value with correct formatting for currencies with specific format', () => {
    langGetter.mockReturnValue(['en-AU', 'en']);
    expect(getFormattedAmount(111111111, 'AUD')).toBe('1,111,111.11');
  });

  test('getFormattedAmount should return value with default formatting if no formatting is associated with a currency', () => {
    expect(getFormattedAmount(11111111, 'USD')).toBe('111,111.11');
  });

  test('getFormattedAmount should return value with default formatting if currency is not found in the mapper', () => {
    expect(getFormattedAmount(11111111, 'ABC')).toBe('111,111.11');
  });

  describe('Tests for malaysians currency formatting', () => {
    test('formatting 11111 MYR to 111.11', () => {
      expect(getFormattedAmount(11111, 'MYR')).toBe('111.11');
    });

    test('formatting 111111 MYR to 1,111.11', () => {
      expect(getFormattedAmount(111111, 'MYR')).toBe('1,111.11');
    });

    test('formatting 11111111 MYR to 111,111.11', () => {
      expect(getFormattedAmount(11111111, 'MYR')).toBe('111,111.11');
    });

    test('formatting 1111111111 MYR to 11,111,111.11', () => {
      expect(getFormattedAmount(1111111111, 'MYR')).toBe('11,111,111.11');
    });

    test('formatting 11111111111 MYR to 111,111,111.11', () => {
      expect(getFormattedAmount(11111111111, 'MYR')).toBe('111,111,111.11');
    });
  });

  test('getCurrencyConfig should refer to local window.currencyList when it is defined', () => {
    currencyList.KWD.denomination = 10000;
    window.currencyList = currencyList;

    const { decimals } = getCurrencyConfig('KWD');

    expect(decimals).toBe(4);
    window.currencyList = null;
  });

  test('getCurrencyConfig should return 2 if experiment is disabled', () => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support, 'off'),
    };

    const { decimals } = getCurrencyConfig('kwd');

    expect(decimals).toBe(2);
  });
});

describe('Tests for mergeCurrencyFormatting', () => {
  test('Function should return static currency object if wrong data type is passed in arguments', () => {
    const data = mergeCurrencyFormatting('dummy data');
    expect(typeof data).toBe('object');
    expect(data.default).toBeDefined();
  });

  test('Function should return merged data when correct data is passed', () => {
    const data = mergeCurrencyFormatting(currencyList);
    expect(typeof data).toBe('object');
    expect(data.default).toBeDefined();
    expect(data.INR.format).toBeDefined();
  });
});

describe('Download Sample File', () => {
  writeSpy.mockImplementation(() => jest.fn());
  saveAsSpy.mockImplementation(() => jest.fn());
  const fileName = 'test';
  let fileFormat = 'xlsx';
  const finalDataSend = [
    {
      category: 'sample_pl_LpoFCooJAk0a2j',
      data: [
        {
          Amount: '',
          'Primary Reference ID': '',
          Email: '',
          Phone: '',
        },
      ],
    },
  ];

  test('should download in xlsx format', () => {
    exportFileAsExcel({ finalDataSend, fileName, fileFormat });
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'test.xlsx');
  });

  test('should download in csv format', () => {
    fileFormat = 'csv';
    exportFileAsExcel({ finalDataSend, fileName, fileFormat });
    expect(FileSaver.saveAs).toHaveBeenCalledWith(new Blob(), 'test.csv');
  });
});

describe('openTicketModal', () => {
  test('openTicketModal should call window.rzpTicketSystem.openModal with the correct arguments', () => {
    const openModalMock = jest.fn();
    const rzpTicketSystemMock = {
      openModal: openModalMock,
    };

    global.rzpTicketSystem = rzpTicketSystemMock;
    openTicketModal({ ticketData: 'example' });

    expect(openModalMock).toHaveBeenCalledWith(expect.stringContaining('ticket-'), {
      ticketData: 'example',
    });
  });
});

describe('Tests for unit conversion', () => {
  beforeAll(() => {
    window.rzp_user = {
      splitz_experiments: getSplitzExperiments(abExperimentsMap.n_exponent_support),
    };
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion when 2 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.12, 'INR')).toBe(10012);
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion when 3 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.12, 'KWD')).toBe(100120);
  });

  test('i18CurrencyConversionFromCommonUnitToMinorUnit should return correct conversion for 2 decimal when currency passed does not exist', () => {
    expect(i18CurrencyConversionFromCommonUnitToMinorUnit(100.12, 'ABC')).toBe(10012);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion when 2 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(100123, 'INR')).toBe(1001.23);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion when 3 decimal currency is passed', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(10012, 'KWD')).toBe(10.012);
  });

  test('i18CurrencyConversionFromMinorUnitToCommonUnit should return correct conversion for 2 decimal when currency passed does not exist', () => {
    expect(i18CurrencyConversionFromMinorUnitToCommonUnit(10012, 'ABC')).toBe(100.12);
  });
});

describe('Tests for `isExperimentActive` function', () => {
  test('`result: ` Should return true when pass result as on', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: 'on',
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeTruthy();
  });
  test('`result: ` Should return false when result have any other value apart from on', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: 'off',
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`result: ` Should return false when result is set as null', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: null,
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`result: ` Should return false when result is set as {}', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: {},
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`result: ` Should return false when result key is rename to testResult or any other name', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          testResult: {},
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`result: ` Should return false when result is set as number', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {
          result: 1,
        },
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`STREAKS_REWARDS_GROWTH: ` Should return false when pass empty object in isExperimentActive function', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {},
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`STREAKS_REWARDS_GROWTH: `Should return false when pass null in isExperimentActive function', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: null,
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });

  test('`variables: ` Should return false when variables is set as null', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: null,
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
  test('`variables: `Should return false when variables is set as empty object', () => {
    const experimentObject = {
      STREAKS_REWARDS_GROWTH: {
        variables: {},
      },
    };
    expect(isExperimentActive(experimentObject.STREAKS_REWARDS_GROWTH)).toBeFalsy();
  });
});

const INPUT_LIST = [
  { amount: '1000', countryCode: 'MY', formattedAmount: '1,000' },
  { amount: '10000', countryCode: 'MY', formattedAmount: '10,000' },
  { amount: '100000', countryCode: 'MY', formattedAmount: '100,000' },
  { amount: '1000000', countryCode: 'MY', formattedAmount: '1,000,000' },
  { amount: '10000000', countryCode: 'MY', formattedAmount: '10,000,000' },
  { amount: '1000', countryCode: 'IN', formattedAmount: '1,000' },
  { amount: '10000', countryCode: 'IN', formattedAmount: '10,000' },
  { amount: '100000', countryCode: 'IN', formattedAmount: '1,00,000' },
  { amount: '1000000', countryCode: 'IN', formattedAmount: '10,00,000' },
  { amount: '10000000', countryCode: 'IN', formattedAmount: '1,00,00,000' },
  { amount: '10000000', countryCode: null, formattedAmount: '1,00,00,000' },
];
describe('Test for convertToLocale function', () => {
  for (const input of INPUT_LIST) {
    const { amount, countryCode, formattedAmount } = input;
    test(`When the amount is ${amount} with the country code ${countryCode}, the corresponding formatted amount is ${formattedAmount}`, () => {
      const result = convertToLocale(amount, countryCode);
      expect(result).toBe(formattedAmount);
    });
  }
});

describe('Test getAmountFieldPlaceholder', () => {
  langGetter.mockReturnValue(['en-IN', 'en']);

  ZERO_EXPONENT_CURRENCIES.forEach((currency) => {
    test(`should return 100 placeholder text for ${currency}`, () => {
      expect(getAmountFieldPlaceholder(currency)).toBe('100');
    });
  });

  TWO_EXPONENT_CURRENCIES.forEach((currency) => {
    test(`should return 100.00 placeholder text for ${currency}`, () => {
      expect(getAmountFieldPlaceholder(currency)).toBe('100.00');
    });
  });

  THREE_EXPONENT_CURRENCIES.forEach((currency) => {
    test(`should return 100.000 placeholder text for ${currency}`, () => {
      expect(getAmountFieldPlaceholder(currency)).toBe('100.000');
    });
  });
});

describe('Tests for isConfigTagAPISupported', () => {
  [
    {
      input: 'MY',
      output: 'MY',
    },
    {
      input: 'SG',
      output: 'SG',
    },
    {
      input: 'UK',
      output: 'UK',
    },
    {
      input: 'US',
      output: 'US',
    },
    {
      input: 'ID',
      output: 'ID',
    },
    {
      input: 'TH',
      output: 'TH',
    },
  ].forEach(({ input, output }) => {
    test('should return the country code if it is supported', () => {
      const result = isConfigTagAPISupported(input);
      expect(result).toBe(output);
    });
  });

  test('should return undefined if the country code is not supported', () => {
    const result = isConfigTagAPISupported('CN');
    expect(result).toBeUndefined();
  });
  test('should return undefined if the input is empty or undefined or null', () => {
    const result1 = isConfigTagAPISupported('');
    const result2 = isConfigTagAPISupported(undefined);
    const result3 = isConfigTagAPISupported(null);
    expect(result1).toBeUndefined();
    expect(result2).toBeUndefined();
    expect(result3).toBeUndefined();
  });
});

describe('common/utils/rzp-utils : getDialCodeFromCountryCode', () => {
  const testCases = [
    { countryCode: 'IN', expected: '+91' },
    { countryCode: 'US', expected: '+1' },
    { countryCode: 'ID', expected: '+62' },
    { countryCode: 'MY', expected: '+60' },
    { countryCode: 'SG', expected: '+65' },
    { countryCode: 'XX', expected: '+91' }, // Invalid country code
    { countryCode: undefined, expected: '+91' }, // No country code provided
  ];

  testCases.forEach(({ countryCode, expected }) => {
    it(`should return ${expected} for country code ${countryCode}`, () => {
      expect(getDialCodeFromCountryCode(countryCode)).toBe(expected);
    });
  });

  describe('common/utils/rzp-utils : getCountryCodes', () => {
    afterEach(() => {
      jest.clearAllMocks();
      global.fetch.mockRestore();
    });

    const mockGeoData = {
      metadata_information: {
        IN: {
          country_name: 'India',
          continent_code: 'AS',
          continent_name: 'Asia',
          alpha_3: 'IND',
          numeric_code: '356',
          flag: 'https://flagcdn.com/in.svg',
          sovereignty: 'UN member state',
          dial_code: '+91',
          supported_currency: ['INR'],
          timezones: {
            'Asia/Kolkata': {
              utc_offset: 'UTC +05:30',
            },
          },
          timezone_of_capital: 'Asia/Kolkata',
          locales: {
            en_IN: {
              name: 'English (India)',
            },
            hi: {
              name: 'Hindi',
            },
            bn: {
              name: 'Bangla',
            },
            te: {
              name: 'Telugu',
            },
            mr: {
              name: 'Marathi',
            },
            ta: {
              name: 'Tamil',
            },
            ur: {
              name: 'Urdu',
            },
            gu: {
              name: 'Gujarati',
            },
            kn: {
              name: 'Kannada',
            },
            ml: {
              name: 'Malayalam',
            },
            or: {
              name: 'Odia',
            },
            pa: {
              name: 'Punjabi',
            },
            as: {
              name: 'Assamese',
            },
            bh: {
              name: 'Bihari languages',
            },
            sat: {
              name: 'Santali',
            },
            ks: {
              name: 'Kashmiri',
            },
            ne: {
              name: 'Nepali',
            },
            sd: {
              name: 'Sindhi',
            },
            kok: {
              name: 'Konkani',
            },
            doi: {
              name: 'Dogri',
            },
            mni: {
              name: 'Manipuri',
            },
            sit: {
              name: 'Sino-Tibetan languages',
            },
            sa: {
              name: 'Sanskrit',
            },
            fr: {
              name: 'French',
            },
            lus: {
              name: 'Lushai',
            },
            inc: {
              name: 'Indic languages',
            },
          },
          default_locale: 'en_IN',
          default_currency: 'INR',
        },
        MY: {
          country_name: 'Malaysia',
          continent_code: 'AS',
          continent_name: 'Asia',
          alpha_3: 'MYS',
          numeric_code: '458',
          flag: 'https://flagcdn.com/my.svg',
          sovereignty: 'UN member state',
          dial_code: '+60',
          supported_currency: ['MYR'],
          timezones: {
            'Asia/Kuala_Lumpur': {
              utc_offset: 'UTC +08:00',
            },
            'Asia/Kuching': {
              utc_offset: 'UTC +08:00',
            },
          },
          timezone_of_capital: 'Asia/Kuala_Lumpur',
          locales: {
            ms_MY: {
              name: 'Malay (Malaysia)',
            },
            en: {
              name: 'English',
            },
            zh: {
              name: 'Chinese',
            },
            ta: {
              name: 'Tamil',
            },
            te: {
              name: 'Telugu',
            },
            ml: {
              name: 'Malayalam',
            },
            pa: {
              name: 'Punjabi',
            },
            th: {
              name: 'Thai',
            },
          },
          default_locale: 'ms_MY',
          default_currency: 'MYR',
        },
        SG: {
          country_name: 'Singapore',
          continent_code: 'AS',
          continent_name: 'Asia',
          alpha_3: 'SGP',
          numeric_code: '702',
          flag: 'https://flagcdn.com/sg.svg',
          sovereignty: 'UN member state',
          dial_code: '+65',
          supported_currency: ['SGD'],
          timezones: {
            'Asia/Singapore': {
              utc_offset: 'UTC +08:00',
            },
          },
          timezone_of_capital: 'Asia/Singapore',
          locales: {
            cmn: {
              name: 'Mandarin Chinese',
            },
            en_SG: {
              name: 'English (Singapore)',
            },
            ms_SG: {
              name: 'Malay (Singapore)',
            },
            ta_SG: {
              name: 'Tamil (Singapore)',
            },
            zh_SG: {
              name: 'Chinese',
            },
          },
          default_locale: 'cmn',
          default_currency: 'SGD',
        },
        US: {
          country_name: 'United States of America (the)',
          continent_code: 'NA',
          continent_name: 'North America',
          alpha_3: 'USA',
          numeric_code: '840',
          flag: 'https://flagcdn.com/us.svg',
          sovereignty: 'UN member state',
          dial_code: '+1',
          supported_currency: ['USD'],
          timezones: {
            'America/Chicago': {
              utc_offset: 'UTC -06:00',
            },
            'America/New_York': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Indianapolis': {
              utc_offset: 'UTC -05:00',
            },
            'America/Kentucky/Louisville': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Vevay': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Vincennes': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Tell_City': {
              utc_offset: 'UTC -06:00',
            },
            'America/Indiana/Marengo': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Petersburg': {
              utc_offset: 'UTC -05:00',
            },
            'America/Kentucky/Monticello': {
              utc_offset: 'UTC -05:00',
            },
            'America/Detroit': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Winamac': {
              utc_offset: 'UTC -05:00',
            },
            'America/Indiana/Knox': {
              utc_offset: 'UTC -06:00',
            },
            'America/Menominee': {
              utc_offset: 'UTC -06:00',
            },
            'America/Phoenix': {
              utc_offset: 'UTC -07:00',
            },
            'America/Los_Angeles': {
              utc_offset: 'UTC -08:00',
            },
            'America/Denver': {
              utc_offset: 'UTC -07:00',
            },
            'America/Boise': {
              utc_offset: 'UTC -07:00',
            },
            'America/Juneau': {
              utc_offset: 'UTC -09:00',
            },
            'America/Sitka': {
              utc_offset: 'UTC -09:00',
            },
            'America/Metlakatla': {
              utc_offset: 'UTC -09:00',
            },
            'America/Yakutat': {
              utc_offset: 'UTC -09:00',
            },
            'America/North_Dakota/New_Salem': {
              utc_offset: 'UTC -06:00',
            },
            'America/North_Dakota/Beulah': {
              utc_offset: 'UTC -06:00',
            },
            'America/North_Dakota/Center': {
              utc_offset: 'UTC -06:00',
            },
            'Pacific/Honolulu': {
              utc_offset: 'UTC -10:00',
            },
            'America/Anchorage': {
              utc_offset: 'UTC -09:00',
            },
            'America/Nome': {
              utc_offset: 'UTC -09:00',
            },
          },
          timezone_of_capital: 'America/New_York',
          locales: {
            en_US: {
              name: 'English (United States)',
            },
            es_US: {
              name: 'Spanish (United States)',
            },
            haw: {
              name: 'Hawaiian',
            },
            fr: {
              name: 'French',
            },
          },
          default_locale: 'en_US',
          default_currency: 'USD',
        },
        // Add more expected countries as needed
      },
    };

    const expectedCountryCodesFromMock = [
      {
        name: 'India',
        dial_code: '+91',
        code: 'IN',
      },
      {
        name: 'Malaysia',
        dial_code: '+60',
        code: 'MY',
      },
      {
        name: 'Singapore',
        dial_code: '+65',
        code: 'SG',
      },
      {
        name: 'United States of America (the)',
        dial_code: '+1',
        code: 'US',
      },
      // Add more expected countries as needed
    ];

    global.fetch = jest.fn(() =>
      Promise.resolve({
        ok: true,
        status: 200,
        json: () => Promise.resolve(mockGeoData),
      }),
    );

    it.only('should correctly extract country data', async () => {
      const countryCodes = await getCountryCodes();
      expect(countryCodes).toEqual(expectedCountryCodesFromMock);
    });

    it('should return default country codes', async () => {
      const mockError = new Error('Error: Error in API response TypeError: Network request failed');
      getAllCountries.mockRejectedValue(mockError);

      const countryCodes = await getCountryCodes();
      expect(countryCodes).toEqual(COUNTRY_CODES);
    });
  });
});

describe('testing autoPrefixUrls util', () => {
  test("should add http if url doesn't contain http/https", () => {
    //adding http as it is not in the url
    let url = autoPrefixUrls('test.com');
    expect(url).toBe('http://test.com');

    //will not add any prefix as the protocol is already mentioned
    url = autoPrefixUrls('https://test.com');
    expect(url).toBe('https://test.com');
  });

  test("should add https if url doesn't contain http/https and shouldUseHttpsProtocol arg. is true", () => {
    //adding https as it is not in the url and want to create a url with https protocol
    let url = autoPrefixUrls('test.com', true);
    expect(url).toBe('https://test.com');

    //will be adding http protocol as shouldUseHttpsProtocol is false
    url = autoPrefixUrls('test.com', false);
    expect(url).toBe('http://test.com');
  });

  test('should not add any prefix if the url does contain http/https', () => {
    let url = autoPrefixUrls('https://test.com');
    expect(url).toBe('https://test.com');

    url = autoPrefixUrls('http://test.com', true);
    expect(url).toBe('http://test.com');
  });
});

describe('Tests humanize function', () => {
  test('should return the humanized string', () => {
    expect(humanize('')).toBe('');
    expect(humanize('hello_world')).toBe('Hello World');

    // handles falsy values
    expect(humanize(undefined)).toBe('');
    expect(humanize(null)).toBe('');
    expect(humanize(0)).toBe('');
    expect(humanize(false)).toBe('');
  });
});
