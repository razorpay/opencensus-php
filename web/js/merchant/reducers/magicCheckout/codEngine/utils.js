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
/*
input => {
        "configs": {
            "cod_engine": false,
            "cod_engine_type": "product",
            "shop_id": "magic-checkout-test-store-1"
        },
        "fee_rules": [],
        "zones": [],
        "item_categories": []
    }
output => {
        "configs": {
            "cod_engine": false,
            "cod_engine_type": "product",
            "engine_type": "advanced",
            "rate_slabs": true/false
            "shop_id": "magic-checkout-test-store-1"
        },
        "fee_rules": [],
        "zones": [], // adds state_count & (country value using country code) to each zone
        "item_categories": []
    }
}
*/
export const formatResponse = (data) => {
  const { configs, fee_rules = [], item_categories = [] } = data;
  let { zones = [] } = data;
  configs.engine =
    [COD_ENGINE_TYPES.PRODUCT, COD_ENGINE_TYPES.LOCATION].includes(configs.cod_engine_type) &&
    fee_rules?.length &&
    zones?.length
      ? COD_ENGINES.ADVANCED
      : COD_ENGINES.BASIC;
  configs.rate_slabs =
    configs.cod_engine_type && configs.cod_engine_type !== COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
  if (zones?.length > 0) {
    zones = buildZonesData(zones);
  }
  let mappingValidation = true;
  if (configs.engine === COD_ENGINES.ADVANCED) {
    if (!item_categories?.length) {
      const hasFeeRules = zones.every((z) => z.fee_rules?.length);
      if (!hasFeeRules) {
        mappingValidation = false;
      }
    } else {
      const hasZones = item_categories.every((c) => c.zones?.length);
      if (!hasZones) {
        mappingValidation = false;
      }
    }
  }
  let editMode = !zones || !fee_rules || zones?.length === 0 || fee_rules?.length === 0;
  if (configs.engine === COD_ENGINES.ADVANCED) {
    if (
      (configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION && !zones?.fee_rules?.length) ||
      (configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT && !item_categories?.zones?.length)
    )
      editMode = true;
  }
  return { configs, zones, fee_rules, item_categories, editMode, mappingValidation };
};
