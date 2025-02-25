import React from 'react';
import { getAppsBaseAssetUrl } from '../../../utils/getAppsAssetUrl';

export const generateMerchantLAEntryScript = () => (
  <script key="merchant-la" src={`${getAppsBaseAssetUrl('la-dashboard')}/la-dashboard.entry.js`} />
);
