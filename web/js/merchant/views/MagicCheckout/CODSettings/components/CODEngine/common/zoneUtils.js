import { GLOBAL_KEY } from 'merchant/views/MagicCheckout/CODSettings/constants';

export const DEPTH_MAP = {
  // commented below line to remove International option in popup, might need it in future
  // [GLOBAL_KEY]: 0,
  COUNTRY: 0,
  STATE: 1,
};
const getTotalSelectableItems = (depth, item, zone = {}) => {
  if (depth === DEPTH_MAP.COUNTRY) {
    if (!zone && item.zone_name) return 0;
    let filterFn = (i) => !i.zone_name;
    if (zone) {
      filterFn = (i) => !i.zone_name || i.zone_name === zone.name;
    }
    return item.states.filter(filterFn).length;
  }
  return 0;
};

const getZoneCondition = ({ zone, itemZone, parentZone }) => {
  if (zone) {
    return itemZone === zone || parentZone === zone;
  } else if (parentZone || itemZone) return false;
  return true;
};

// builds countries list form /countries api response. Add total states, total selectable children, selected children properties to each country.
// Adds zone name & parent index to states to handle enable/disabling of zone item & indeterminate status of parent checkboxes
export const buildCountriesData = (countries, zone = {}) => {
  // eslint-disable-next-line no-unused-vars
  let totalItemsForGlobal = 0;
  const countriesWithStates = {};
  // if (countries?.[0]?.code !== GLOBAL_KEY) {
  //   countries.unshift({
  //     code: GLOBAL_KEY,
  //     name: GLOBAL_KEY,
  //     depth: 0,
  //     selected: false,
  //     zone_name: null,
  //   });
  // }

  const formattedCountries = countries.map((country, index) => {
    country = {
      index,
      code: country.code,
      name: country.name,
      depth: 0,
      zone_name: country.zone_name,
      selected: country.zone_name === zone.name,
      total_children: country.states?.length || 0,
      total_selectable_children: getTotalSelectableItems(DEPTH_MAP.COUNTRY, country, zone) || 0,
      parentIndex: 0,
      states: country.states ?? {},
      total_selected: 0,
      visible_status: 'all',
    };

    if (country.states?.length > 0) {
      countriesWithStates[country.code] = true;
      const statesMap = {};

      country.states.forEach((state) => {
        statesMap[state.code] = {
          code: state.code,
          name: state.name,
          depth: 1,
          zone_name: state.zone_name,
          parent: country.code,
          parentIndex: index,
          selected: false,
          visible_status: 'all',
        };
        if (
          getZoneCondition({
            zone: zone?.name,
            itemZone: state.zone_name,
            parentZone: country.zone_name,
          })
        ) {
          totalItemsForGlobal += 1;
        }
        if (state.zone_name === zone.name || country.zone_name === zone.name) {
          statesMap[state.code].selected = true;
          country.total_selected += 1;
        }
      });
      country.states = statesMap;
    } else if (!country.zone_name && country.code !== GLOBAL_KEY) totalItemsForGlobal += 1;
    if (
      index !== 0 &&
      country.total_selectable_children > 0 &&
      country.total_selected === country.total_selectable_children &&
      !country.selected
    ) {
      country.selected = true;
    }
    return country;
  });
  // commented below line to remove International option in popup, might need it in future
  // formattedCountries[0].total_selectable_children = totalItemsForGlobal;
  return { allCountries: formattedCountries, countriesWithStates };
};

export const forAllStates = (
  item,
  fn,
  opts = {
    operation: 'forEach',
  },
) => {
  switch (opts.operation) {
    case 'some':
      return Object.keys(item.states)[opts.operation]((code) => {
        return fn(item.states[code]);
      });
    default:
      return Object.keys(item.states)[opts.operation]((code) => {
        !item.states[code].zone_name && fn(item.states[code]);
      });
  }
};

export const updateTotalSelectedStatus = (countries, code, checked) => {
  if (checked) {
    countries[code].total_selected = countries[code].total_selectable_children;
  } else {
    countries[code].total_selected = 0;
  }
};

export const forAllCountries = (countries, status, fn) => {
  countries.forEach((c, code) => {
    updateTotalSelectedStatus(countries, code, status);
    forAllStates(countries[code], (state) => (state.selected = status));
    !countries[code].zone_name && fn(countries[code]);
  });
};

export const marginLeft = [0, '25px', '40px'];

export const getLocationsPayload = (countries, zone) => {
  // global check
  const locations = [];
  // return locations;
  for (let i = 0; i < countries.length; i++) {
    const country = countries[i];
    const { total_selected, total_children, selected, states } = country;
    // eslint-disable-next-line no-continue
    if (!selected && total_selected === 0 && total_children !== 0) continue;
    const fullCountrySelected = total_children === 0 ? selected : total_children === total_selected;
    if (fullCountrySelected) {
      let id;
      if (zone) {
        const existingZone = zone.locations?.find(
          (z) => z.location_type === 'country' && z.country_code === country.code,
        );
        if (existingZone) id = existingZone.id;
      }
      locations.push({
        id,
        location_type: 'country',
        country_code: country.code,
        type: 'serviceable',
      });
    } else {
      const statesArr = Object.keys(states || {});
      if (statesArr.length > 0) {
        // eslint-disable-next-line no-loop-func
        statesArr.forEach((state_code) => {
          const state = country.states[state_code];
          if (state.selected) {
            let id;
            if (zone) {
              const existingZone = zone.locations.find(
                (l) => l.location_type === 'state' && l.state_code === state_code,
              );
              if (existingZone) id = existingZone.id;
            }
            locations.push({
              id,
              location_type: 'state',
              country_code: country.code,
              type: 'serviceable',
              state_code,
            });
          }
        });
      }
    }
  }
  return locations;
};
