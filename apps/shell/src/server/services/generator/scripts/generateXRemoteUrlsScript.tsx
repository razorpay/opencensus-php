/**
 * Purpose:
 * This file is responsible for setting up the initial URLs
 * for X remote bundles based on the environment and deployment type.
 *
 * Usage:
 * Ensure that any modifications are synchronized with the corresponding
 * X file to maintain consistency across environments.
 *
 * @file X: src/js/bootstrap.ts
 */

import React from 'react';
import { STAGE } from '@apps/shell/src/env';

export const generateXRemoteUrlsScript = () => {
  const isProduction = STAGE === 'production';
  const isCanary = STAGE === 'canary';
  const isDevelopment = STAGE === 'development';

  const xOrigin = (() => {
    if (isProduction || isCanary) return 'https://x.razorpay.com';
    if (isDevelopment) return 'https://localhost:8880';
    return 'https://x.dev.razorpay.in';
  })();
  const xUrl = isCanary ? `${xOrigin}/canary/dist` : `${xOrigin}/dist`;

  const xVendorPortalUrl = (() => {
    const origin = isProduction || isCanary ? xOrigin : 'https://x.dev.razorpay.in';
    return isCanary
      ? `${origin}/federated-bundles/x-vendor-portal/canary`
      : `${origin}/federated-bundles/x-vendor-portal`;
  })();

  const coreBundlesOrigin = (() => {
    if (isProduction || isCanary) return 'https://dashboard-assets.razorpay.com';
    if (isDevelopment) return 'https://localhost:8000';
    return 'https://dashboard.dev.razorpay.in';
  })();
  const coreBundlesUrl = isCanary
    ? `${coreBundlesOrigin}/dashboard-canary/core-bundles`
    : `${coreBundlesOrigin}/dashboard/core-bundles`;

    const PROD_CDN_BASE_URL = 'https://cdn.razorpay.com';
    const STAGE_CDN_BASE_URL = 'https://betacdn.np.razorpay.in';

    const capitalUrls = (() => {
      if (isProduction || isCanary) {
        const capitalUrlPath = `${PROD_CDN_BASE_URL}/capital/federated-bundles`;
        const capitalUrlCanaryPath = `${capitalUrlPath}${isCanary ? '/canary' : ''}`;

        return `
          window.capitalCardsDashboardUrl = "${capitalUrlPath}/cards-dashboard";
          window.capitalCashAdvanceEmiUrl = "${capitalUrlCanaryPath}/cash-advance-emi";
          window.capitalBnplDasbhoardUrl = "${capitalUrlPath}/bnpl-dashboard";
          
          const canUseModernBuild = "noModule" in document.createElement("script");
          const onboardingRemoteEntryFilename = "application." + (canUseModernBuild ? "modern" : "legacy") + ".remoteEntry.js";
          window.capitalOnboardingRemoteUrl = "${PROD_CDN_BASE_URL}/capital-onboarding/application/" + onboardingRemoteEntryFilename;
        `;
      }

      return `
        const branch = new URLSearchParams(location.search).get("branch") || "master";
        const capitalUrlPath = "${STAGE_CDN_BASE_URL}/capital/" + branch + "/federated-bundles";

        window.capitalCardsDashboardUrl = capitalUrlPath + "/cards-dashboard";
        window.capitalCashAdvanceEmiUrl = capitalUrlPath + "/cash-advance-emi";
        window.capitalBnplDasbhoardUrl = capitalUrlPath + "/bnpl-dashboard";
          
        const canUseModernBuild = "noModule" in document.createElement("script");
        const onboardingRemoteEntryFilename = "application." + (canUseModernBuild ? "modern" : "legacy") + ".remoteEntry.js";
        window.capitalOnboardingRemoteUrl = "${PROD_CDN_BASE_URL}/capital-onboarding/application/" + onboardingRemoteEntryFilename;

        const xOrigin = "${xOrigin}";
        if (xOrigin.includes("x.dev.razorpay.in")) {
          window.capitalOnboardingRemoteUrl = xOrigin + "/federated-bundles/capital-onboarding/application/" + onboardingRemoteEntryFilename;
        }
      `;
    })();

    return (
      <script
        key="x-remote-urls-script"
        dangerouslySetInnerHTML={{
          __html: `
            window.xUrl = ${JSON.stringify(xUrl)};
            window.xVendorPortalUrl = ${JSON.stringify(xVendorPortalUrl)};
            window.coreBundlesUrl = ${JSON.stringify(coreBundlesUrl)};

            ${capitalUrls}
        `,
        }}
      />
    );
};
