import React from 'react';
import { Request } from 'express';
import { renderToStaticMarkup } from 'react-dom/server';
import { ServerStyleSheet } from 'styled-components';
import { Html } from '@apps/shell/src/server/components';
import {
  generateHotjarScript,
  generateInterfaceInitializerScript,
  generateRzpUserScriptElement,
  generateAnalyticsScriptElement,
  generateEnvironmentKeysGetterScriptElement,
  getGoogleAPIScriptElement,
  generateBladeCoverageScriptElement,
  generateAnalyticsBundleScriptElement,
  generateSignUpRedirectScript,
  generateAnalyticsInitializationScriptElement,
  generateHolidayScriptElement,
  generateMerchantLAEntryScript,
  generateBELAControllerScript,
  beMiscellaneousLinkElements,
  generateMountRemoteSafelyFnScriptElement,
} from '@apps/shell/src/server/services/generator';
import { IS_PRODUCTION } from '@apps/shell/src/env';

type AppLocals = {
  user: any;
  org: any;
};

export const generateLATemplate = async (req: Request, appLocals: AppLocals): Promise<string> => {
  const isConfirmed = appLocals.user.user.confirmed;
  const isMobileConfirmed = appLocals.user.user.contact_mobile_verified;

  const requestOrigin = req.query.auth_source;
  const isRequestOriginViaWebsite = requestOrigin === 'website';
  const isRequestOriginViaWebsiteHompage = requestOrigin === 'website_homepage';

  const scriptTags = [
    generateEnvironmentKeysGetterScriptElement(),
    generateMountRemoteSafelyFnScriptElement(),
    generateRzpUserScriptElement(appLocals.user),
    generateInterfaceInitializerScript(),
    generateAnalyticsScriptElement(),
    getGoogleAPIScriptElement(),
    generateAnalyticsBundleScriptElement(),
    generateHolidayScriptElement(),
    generateSignUpRedirectScript(),
    generateMerchantLAEntryScript(),
    generateAnalyticsInitializationScriptElement(),
    generateBELAControllerScript(appLocals),
  ];

  if (IS_PRODUCTION) {
    [generateHotjarScript(), generateBladeCoverageScriptElement()].forEach((script) =>
      scriptTags.push(script),
    );
  }

  const linkTags = [
    ...beMiscellaneousLinkElements({
      isConfirmed,
      isMobileConfirmed,
      isRequestOriginViaWebsite,
      isRequestOriginViaWebsiteHompage,
      entryMode: 'la-dashboard',
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
