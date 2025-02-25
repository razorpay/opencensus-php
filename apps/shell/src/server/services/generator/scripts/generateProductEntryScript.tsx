import React from 'react';
import { getAppsBaseAssetUrl } from '../../../utils/getAppsAssetUrl';

export const generateProductEntryScript = () => (
  <script
    key="product-dashboard"
    src={`${getAppsBaseAssetUrl(
      'payments-dashboard',
    )}/payments-dashboard.entry.js`}
  />
);
