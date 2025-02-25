import { CDN_BASE_URL } from '@apps/shell/src/env';
import React from 'react';

export const generateAnalyticsBundleScriptElement = () => (
  <script key="cdn-analytics-bundle" src={`${CDN_BASE_URL}/static/analytics/bundle.js`} />
);
