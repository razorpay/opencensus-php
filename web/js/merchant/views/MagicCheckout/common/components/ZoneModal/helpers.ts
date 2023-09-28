import { DEPTH_MAP, GLOBAL_KEY } from './constants';
import { Country, CountryMap, Location, State, Zone } from './types';

const getTotalSelectableItems = (
  depth: number,
  item: Country,
  zone: Zone | Record<string, unknown> = {},
) => {
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
export const buildCountriesData = (
  countries: Country[],
  zone: Zone | Record<string, unknown> = {},
) => {
  // eslint-disable-next-line no-unused-vars, @typescript-eslint/no-unused-vars
  let totalItemsForGlobal = 0;
  const countriesWithStates = {};
  // to add Internation option at the top of the list.
  if (countries?.[0]?.code !== GLOBAL_KEY) {
    countries.unshift({
      code: GLOBAL_KEY,
      name: GLOBAL_KEY,
      depth: DEPTH_MAP[GLOBAL_KEY],
      selected: false,
      zone_name: null,
      states: [],
    });
  }

  const formattedCountries = countries.map((country, index) => {
    const formattedCountry: CountryMap = {
      index,
      code: country.code,
      name: country.name,
      depth: country.depth ?? DEPTH_MAP.COUNTRY,
      zone_name: country.zone_name,
      selected: zone?.name ? country.zone_name === zone.name : false,
      total_children: country.states?.length || 0,
      total_selectable_children: getTotalSelectableItems(DEPTH_MAP.COUNTRY, country, zone) || 0,
      parentIndex: 0,
      states: country.states,
      total_selected: 0,
      visible_status: 'all',
    };

    if ((formattedCountry.states as State[])?.length > 0) {
      countriesWithStates[formattedCountry.code] = false;
      const statesMap = {};

      (formattedCountry.states as State[]).forEach((state) => {
        statesMap[state.code] = {
          code: state.code,
          name: state.name,
          depth: DEPTH_MAP.STATE,
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
            parentZone: formattedCountry.zone_name,
          })
        ) {
          totalItemsForGlobal += 1;
        }
        if (zone?.name && (state.zone_name === zone.name || country.zone_name === zone.name)) {
          statesMap[state.code].selected = true;
          formattedCountry.total_selected += 1;
        }
      });
      formattedCountry.states = statesMap;
    } else if (!country.zone_name && country.code !== GLOBAL_KEY) totalItemsForGlobal += 1;
    if (
      index !== 0 &&
      formattedCountry.total_selectable_children > 0 &&
      formattedCountry.total_selected === formattedCountry.total_selectable_children &&
      !formattedCountry.selected
    ) {
      formattedCountry.selected = true;
    }
    return formattedCountry;
  });
  // commented below line to remove International option in popup, might need it in future
  formattedCountries[0].total_selectable_children = totalItemsForGlobal;
  return { allCountries: formattedCountries, countriesWithStates };
};

// perform some operation on all states
export const forAllStates = (
  item: CountryMap,
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
        if (!item.states[code].zone_name) {
          fn(item.states[code]);
        }
      });
  }
};

export const updateTotalSelectedStatus = (countries, code, checked): any => {
  if (checked) {
    countries[code].total_selected = countries[code].total_selectable_children;
  } else {
    countries[code].total_selected = 0;
  }
};

// perform some operation on all counrties
export const forAllCountries = (countries: CountryMap[], status: boolean, fn): any => {
  countries.forEach((c, code) => {
    updateTotalSelectedStatus(countries, code, status);
    forAllStates(countries[code], (state) => (state.selected = status));
    if (!countries[code].zone_name) {
      fn(countries[code]);
    }
  });
};

export const getLocationsPayload = (countries: CountryMap[], zone: Zone | undefined) => {
  const locations: Location[] = [];
  for (let i = 0; i < countries.length; i++) {
    const country = countries[i];
    if (country.code !== GLOBAL_KEY) {
      // eslint-disable-next-line @typescript-eslint/naming-convention
      const { total_selected, total_children, selected, states } = country;
      if (
        !selected &&
        ((total_selected === 0 && total_children !== 0) || total_selected === total_children)
      )
        // eslint-disable-next-line no-continue
        continue;
      const isFullCountrySelected =
        total_children === 0 ? selected : total_children === total_selected;
      if (isFullCountrySelected) {
        let id;
        if (zone) {
          const existingZone = zone.locations?.find(
            (z) => z.location_type === 'country' && z.country_code === country.code,
          );
          // eslint-disable-next-line max-depth
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
  }

  return locations;
};
