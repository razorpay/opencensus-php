import React from 'react';
import { IS_PRODUCTION } from '@apps/shell/src/env';
import { Html } from '@apps/shell/src/server/components';
import { Request } from 'express';
import { ServerStyleSheet } from 'styled-components';
import { fetchNavigation } from '@apps/shell/src/server/services/queries/navigation';
import { queryClient } from '@apps/shell/src/shared/store/queryClient';
import { renderToStaticMarkup } from 'react-dom/server';
import {
  generateBankingServicePGClientScript,
  generateDCSScriptElement,
  generateHotjarScript,
  generateInterfaceInitializerScript,
  generateRzpBEControllerScripts,
  generateRzpUserScriptElement,
  generateAnalyticsScriptElement,
  generateEnvironmentKeysGetterScriptElement,
  generateSignUpRedirectScript,
  getGoogleAPIScriptElement,
  generateBladeCoverageScriptElement,
  generateAnalyticsInitializationScriptElement,
  generateAnalyticsBundleScriptElement,
  generateHolidayScriptElement,
  beMiscellaneousLinkElements,
  generateProductEntryScript,
  generateMountRemoteSafelyFnScriptElement,
  generateRazorAnalyticsScriptElement,
} from '@apps/shell/src/server/services/generator';

type AppLocals = {
  user: any;
  org: any;
  clientTemplate: string;
};

export const generateMerchantTemplate = async (
  req: Request,
  appLocals: AppLocals,
): Promise<string> => {
  const isConfirmed = appLocals.user?.user?.confirmed;
  const isMobileConfirmed = appLocals.user?.user?.contact_mobile_verified;

  const requestOrigin = req.query.auth_source;
  const isRequestOriginViaWebsite = requestOrigin === 'website';
  const isRequestOriginViaWebsiteHompage = requestOrigin === 'website_homepage';

  const isPreSignupComplete = appLocals.user?.pre_signup_complete;

  try {
    await fetchNavigation(queryClient, {});
  } catch (err) {
    console.error('Error while fetching navigation data', err);
  }

  const scriptTags = [
    generateEnvironmentKeysGetterScriptElement(),
    generateMountRemoteSafelyFnScriptElement(),
    generateRzpUserScriptElement(appLocals.user),
    generateDCSScriptElement(),
    generateBankingServicePGClientScript(appLocals.clientTemplate),
    generateInterfaceInitializerScript(),
    generateAnalyticsScriptElement(),
    generateSignUpRedirectScript(),
    getGoogleAPIScriptElement(),
    generateAnalyticsBundleScriptElement(),
    generateHolidayScriptElement(),
    generateAnalyticsInitializationScriptElement(),
    generateProductEntryScript(),
    generateRazorAnalyticsScriptElement(),
  ];

  if (
    (isConfirmed || isMobileConfirmed) &&
    isPreSignupComplete &&
    !isRequestOriginViaWebsite &&
    !isRequestOriginViaWebsiteHompage
  ) {
    scriptTags.push(generateRzpBEControllerScripts(req, appLocals));
  }

  if (IS_PRODUCTION) {
    [
      generateHotjarScript(),
      generateBladeCoverageScriptElement(),
    ].forEach((script) => scriptTags.push(script));
  }

  const linkTags = [
    ...beMiscellaneousLinkElements({
      isConfirmed,
      isMobileConfirmed,
      isRequestOriginViaWebsite,
      isRequestOriginViaWebsiteHompage,
      entryMode: 'payments-dashboard',
    }),
  ];

  const sheet = new ServerStyleSheet();
  const styleTags = sheet.getStyleElement();

  const scriptTagsWithTypeModule = scriptTags.map((scriptTag) => {
    return (scriptTag.key as string).endsWith('.js')
      ? {
          ...scriptTag,
          props: {
            ...scriptTag.props,
            // type: 'module',
          },
        }
      : scriptTag;
  });

  return `<!doctype html>
          ${renderToStaticMarkup(
            <Html
              scriptTags={scriptTagsWithTypeModule}
              linkTags={linkTags}
              styleTags={styleTags}
              isOldFlow
            />,
          )}`;
};
