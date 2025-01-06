import {
  OptionType,
  StatesAndCitiesType,
} from 'merchant/views/StoreSettings/StoresList/containers/StoreGroupsContainer/components/StoreFilter/types';

/**
 * Construct options object from values
 * @param {String[]} values
 * @returns {Array} - the array containing the options object
 */
const constructOptionsFromValues = (values: string[]) => {
  const result: OptionType[] = [];
  for (const value of values) {
    if (value) {
      result.push({ label: value, value });
    }
  }
  return result;
};

/**
 * Get the left slots for the Store Filter component
 * @param {Object} options - the object containing the states and cities from API response
 * @returns {Object} - the object containing the left slots for the Store Filter component
 */

const getLeftSlots = (options: StatesAndCitiesType) => ({
  slot: [
    {
      label: 'State',
      value: 'states',
      options: constructOptionsFromValues(options.states),
    },
    {
      label: 'City',
      value: 'cities',
      options: constructOptionsFromValues(options.cities),
    },
  ],
});

export { getLeftSlots };
