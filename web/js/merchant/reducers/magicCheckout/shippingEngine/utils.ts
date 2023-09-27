import countriesWithCodes from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/common/countries';
import { Zone } from 'merchant/views/MagicCheckout/common/components/ZoneModal/types';

import { DefaultProfile, ShippingProfile, ShippingSummaryAPIResponse } from './types';

// formats zone api response - adds stateCount & countryName (country_code <-> countryName) to each zone to show them in the preview table
export const buildZonesData = (zones: Zone[] = []): Zone[] => {
  return zones.map((zone) => {
    const country_map = {};
    let stateCount: number | string = 0;
    zone.locations?.forEach((loc) => {
      country_map[loc.country_code] = countriesWithCodes[loc.country_code].name;
      if (loc.location_type === 'country') {
        stateCount = countriesWithCodes[loc.country_code].state_count;
      } else if (loc.location_type === 'state') (stateCount as number)++;
    });
    if (Object.keys(country_map).length > 1) stateCount = 'NA';
    zone.state_count = stateCount;
    zone.shipping_methods = zone.shipping_methods || [];
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
export const formatResponse = (
  data: ShippingSummaryAPIResponse,
): { formattedProfiles: Record<string, ShippingProfile>; defaultProfile: DefaultProfile } => {
  const { shipping_profiles } = data;

  const formattedProfiles: Record<string, ShippingProfile> = {};

  const defaultProfile: DefaultProfile = {};
  shipping_profiles.forEach((profile) => {
    if (profile.is_default) {
      defaultProfile.name = profile.name;
    }

    return (formattedProfiles[profile.name] = profile);
  });

  return { formattedProfiles, defaultProfile };
};
