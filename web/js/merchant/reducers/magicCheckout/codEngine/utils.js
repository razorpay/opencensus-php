import { COD_ENGINES, COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import countriesWithCodes from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/countries';

// formats zone api response - adds stateCount & countryName (country_code <-> countryName) to each zone to show them in the preview table
export const buildZonesData = (zones = []) => {
  return zones.map((zone) => {
    const country_map = {};
    let stateCount = 0;
    zone.locations?.forEach((loc) => {
      country_map[loc.country_code] = countriesWithCodes[loc.country_code].name;
      if (loc.location_type === 'country') {
        stateCount = countriesWithCodes[loc.country_code].state_count;
      } else if (loc.location_type === 'state') stateCount++;
    });
    if (Object.keys(country_map).length > 1) stateCount = 'NA';
    zone.state_count = stateCount;
    zone.countries = Object.values(country_map);
    return zone;
  });
};

export const formatResponse = (data) => {
  const { configs, fee_rules = [] } = data;
  let { zones = [] } = data;
  configs.engine =
    configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT ? COD_ENGINES.ADVANCED : COD_ENGINES.BASIC;
  configs.rate_slabs =
    configs.cod_engine_type && configs.cod_engine_type !== COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
  if (zones?.length > 0) {
    zones = buildZonesData(zones);
  }
  const editMode = !zones || !fee_rules || zones?.length === 0 || fee_rules?.length === 0;
  return { configs, zones, fee_rules, editMode };
};
