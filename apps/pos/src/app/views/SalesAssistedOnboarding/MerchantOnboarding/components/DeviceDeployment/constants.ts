export const DEVICE_TESTING_AMOUNT_REGEX = /^[0-9]*\.?[0-9]*$/;

export const WD10_WIFI_CONFIG_INSTRUCTIONS = [
  {
    title:
      'Long press ‘-” button on the Soundbox to switch from Mobile Network (SIM) to Wifi OR Wifi to Mobile Network (SIM)',
    id: 1,
  },
  {
    title: 'Long press ‘+” button to enable Wifi Configuration mode',
    id: 2,
  },
  {
    title: 'Connect this mobile to the Wifi network starting with “RZP” within 3 minutes',
    id: 3,
  },
  {
    title: 'Return to this Wifi configuration screen',
    id: 4,
  },
  {
    title: 'Press “Configure Device Wifi” button to enter Wifi username and password',
    id: 5,
  },
];

export const WD10_WIFI_CONFIG_URL = 'http://192.168.1.1/';
