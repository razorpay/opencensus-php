module.exports = [
  {
    deprecatedSpecifiers: ['i18CurrencyConversionFromMinorUnitToCommonUnit', 'paiseToRupees'],
    deprecatedSpecifiersFrom: `common/utils/rzp-utils`,
    importPackageFrom: '@razorpay/i18nify-js/currency',
    specifierToImport: 'convertToMajorUnit',
  },
  {
    deprecatedSpecifiers: ['i18CurrencyConversionFromCommonUnitToMinorUnit', 'rupeesToPaise'],
    deprecatedSpecifiersFrom: `common/utils/rzp-utils`,
    importPackageFrom: '@razorpay/i18nify-js/currency',
    specifierToImport: 'convertToMinorUnit',
  },
  {
    deprecatedSpecifiers: ['getCurrency'],
    deprecatedSpecifiersFrom: 'common/ui/Amount',
    importPackageFrom: '@razorpay/i18nify-js/currency',
    specifierToImport: 'getCurrencyList',
  },
  {
    deprecatedSpecifiers: ['getCurrencySymbol'],
    deprecatedSpecifiersFrom: 'common/ui/Amount',
    importPackageFrom: '@razorpay/i18nify-js/currency',
    specifierToImport: 'getCurrencySymbol',
  },
  {
    deprecatedSpecifiers: ['getFormattedAmount'],
    deprecatedSpecifiersFrom: 'common/utils/rzp-utils',
    importPackageFrom: '@razorpay/i18nify-js/currency',
    specifierToImport: 'getFormattedAmountByParts',
  },
  {
    deprecatedSpecifiers: [
      'humanReadableIndian',
      'humanReadableIndianCurrency',
      'i18HumanReadableNumerals',
      'i18HumanReadableCurrency',
    ],
    deprecatedSpecifiersFrom: 'common/utils/numerals',
    importPackageFrom: 'common/utils/numerals',
    specifierToImport: 'i18nifyHumanReadable',
  },
  {
    deprecatedSpecifiers: ['isPhone', 'isMobile'],
    deprecatedSpecifiersFrom: 'common/utils/validators',
    importPackageFrom: '@razorpay/i18nify-js/phoneNumber',
    specifierToImport: 'isValidPhoneNumber',
  },
];
