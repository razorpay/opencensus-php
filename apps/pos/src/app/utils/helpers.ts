import {
  PosActivationStatus,
  AllMerchantsStatusType,
  CartDeviceType,
  PosDeviceDescription,
  DevicePlanType,
  FeeCategory,
  DevicePricingBreakupType,
  RouteType,
} from '../types';
import { fetchCountByStatus } from './apis';

export const getAllMerchantsStatus = (date: Date): Array<AllMerchantsStatusType> => {
  const statusesArray: Array<PosActivationStatus> = Object.values(PosActivationStatus);
  const result = statusesArray.map((status) => {
    return {
      // TODO: Promise.all can be used here
      count: fetchCountByStatus(status, date),
      status,
    };
  });
  return result;
};

export const joinWordsInCamelCase = (words: Array<string>): string => {
  if (words.length === 0) {
    return '';
  }
  // Convert the first word to lowercase
  const camelCaseWords = [words[0].toLowerCase()];

  // Convert the rest of the words, capitalizing the first letter of each
  for (let i = 1; i < words.length; i++) {
    camelCaseWords.push(words[i].charAt(0).toUpperCase() + words[i].slice(1));
  }

  // Join the words and return the camelCase string
  return camelCaseWords.join('');
};

export const capatalizeWord = (word: string): string => {
  return word[0].toUpperCase() + word.slice(1);
};

export const isDevicePresentInCart = (
  devices: Array<CartDeviceType>,
  device: PosDeviceDescription,
) => {
  return devices.findIndex((cartDevice) => cartDevice.deviceDescription.code === device.code) >= 0;
};

export const createNewCartDevice = (deviceDescription: PosDeviceDescription) => {
  const deviceStandardPricing = deviceDescription.pricing;
  const defaultPlanType = deviceStandardPricing[0].type;
  const defaultPlanIdx = deviceStandardPricing.findIndex(
    (pricing) => pricing.type === defaultPlanType,
  );
  const pricingBreakup: Array<DevicePricingBreakupType> = deviceStandardPricing[
    defaultPlanIdx
  ].breakups.map((breakup) => ({
    type: breakup.key,
    charges: breakup.value,
    category: FeeCategory.STANDARD,
  }));

  const cartDevice: CartDeviceType = {
    count: 1,
    devicePricing: {
      is_advance_rental_enabled: false,
      is_purchase_paper_roll_enabled: false,
      pricing_breakup: pricingBreakup,
      type: defaultPlanType as DevicePlanType,
    },
    deviceDescription,
  };

  return cartDevice;
};

export const getUrlFromRouteName = (name: string, routesObj: RouteType): string => {
  if (routesObj[name]) return `/${routesObj[name].root}`;
  let route = '';
  Object.keys(routesObj).forEach((k) => {
    const newRoutesObj = routesObj[k];
    const localRoute = `${newRoutesObj.root}${
      newRoutesObj.nested_routes ? `/${getUrlFromRouteName(name, newRoutesObj.nested_routes)}` : ''
    }`;
    if (localRoute != '') {
      route = localRoute;
    }
  });
  return route;
};
