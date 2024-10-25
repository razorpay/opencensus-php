import CODSettingsTab from 'merchant/views/MagicCheckout/Settings/containers/CODSettingsTab';
import ConfigDashboard from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard';

import { PLATFORMS } from 'merchant/views/MagicCheckout/constants';

import { GenericRecord } from 'merchant/views/MagicCheckout/types';

export const COD_ROUTES = {
  [PLATFORMS.SHOPIFY]: [
    {
      className: 'cod-settings',
      label: 'COD Settings',
      path: '/magic/settings/cod-settings/settings',
      condition: (_user: GenericRecord) => _user?.isMagicCODEngineEnabled as boolean,
      Component: CODSettingsTab,
      onRCOD: true,
    },
    {
      className: 'pl-configurations-container',
      path: '/magic/settings/cod-settings/cod-to-prepaid',
      label: 'Convert COD to Prepaid',
      Component: ConfigDashboard,
      condition: (_user: GenericRecord) => _user?.isMagicPrepayCODEnabled as boolean,
    },
  ],
  [PLATFORMS.WOOCOMMERCE]: [
    {
      className: 'cod-settings',
      label: 'COD Settings',
      path: '/magic/settings/cod-settings/settings',
      condition: (_user: GenericRecord) => _user?.isMagicCODEngineEnabled as boolean,
      Component: CODSettingsTab,
    },
    {
      className: 'pl-configurations-container',
      path: '/magic/settings/cod-settings/cod-to-prepaid',
      label: 'Convert COD to Prepaid',
      Component: ConfigDashboard,
      condition: (_user: GenericRecord) => _user?.isMagicPrepayCODEnabled as boolean,
    },
  ],
};
