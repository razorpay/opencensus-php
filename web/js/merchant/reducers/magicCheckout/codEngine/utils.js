import { COD_ENGINES, COD_ENGINE_TYPES } from 'merchant/views/MagicCheckout/CODSettings/constants';
import { SERVICEABILITY_TYPES } from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/constants';
import countriesWithCodes from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/countries';
import { RCOD_APP_NAME } from 'merchant/views/MagicCheckout/common/constants';

export const determineCodEngineType = (rules = []) => {
  const hasRates = rules.some((rule) => rule.fee > 0);

  return hasRates ? COD_ENGINE_TYPES.SLAB_RATE : COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
};

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
            "cod_engine": false, / "rcod": false, // in case of rcod app
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
            "rcod": false,
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
  const { configs = {}, fee_rules = [], item_categories = [] } = data;
  let { zones = [] } = data;
  const isRcodData = configs.hasOwnProperty(RCOD_APP_NAME) && !configs.cod_engine;

  if (isRcodData && fee_rules.length && !configs.cod_engine_type) {
    /**
     * In case the user saves the fee rules and disables the engine w/o
     * hitting 'Save & apply', we won't get type the next time user
     * tries to enable the engine
     */
    configs.cod_engine_type = determineCodEngineType(fee_rules);
  }

  configs.engine =
    [COD_ENGINE_TYPES.PRODUCT, COD_ENGINE_TYPES.LOCATION].includes(configs.cod_engine_type) &&
    fee_rules?.length &&
    zones?.length &&
    !isRcodData
      ? COD_ENGINES.ADVANCED
      : COD_ENGINES.BASIC;
  configs.rate_slabs =
    configs.cod_engine_type && configs.cod_engine_type !== COD_ENGINE_TYPES.SLAB_ELIGIBILITY;
  if (zones?.length > 0) {
    zones = isRcodData ? [] : buildZonesData(zones);
  }
  let mappingValidation = true;
  const hasFeeRules = zones?.length ? zones.every((z) => z.fee_rules?.length) : false;
  const hasZones = item_categories?.length
    ? item_categories.every((c) => c.type === SERVICEABILITY_TYPES.BLACKLISTED || c.zones?.length)
    : false;

  if (configs.engine === COD_ENGINES.ADVANCED) {
    if (configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION && !hasFeeRules) {
      mappingValidation = false;
    } else if (configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT && !hasZones) {
      mappingValidation = false;
    }
  }
  let editMode =
    (!configs.cod_engine && !configs.rcod) ||
    !zones ||
    !fee_rules ||
    (zones?.length === 0 && !isRcodData) ||
    fee_rules?.length === 0;
  if (configs.engine === COD_ENGINES.ADVANCED) {
    if (
      (configs.cod_engine_type === COD_ENGINE_TYPES.LOCATION && !hasFeeRules) ||
      (configs.cod_engine_type === COD_ENGINE_TYPES.PRODUCT && !hasZones)
    ) {
      editMode = true;
    }
  }
  return { configs, zones, fee_rules, item_categories, editMode, mappingValidation };
};
