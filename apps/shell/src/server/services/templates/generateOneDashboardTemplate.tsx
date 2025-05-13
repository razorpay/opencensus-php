import React from 'react';
import { Request } from 'express';
import { renderToStaticMarkup, renderToString } from 'react-dom/server';
import { ChunkExtractor } from '@loadable/server';
import { HelmetContext } from 'react-helmet-async';
import { ServerStyleSheet } from 'styled-components';
import { dehydrate } from '@tanstack/react-query';
import { fetchNavigation } from '@apps/shell/src/server/services/queries/navigation';
import { queryClient } from '@apps/shell/src/shared/store/queryClient';
import { Html } from '@apps/shell/src/server/components';
import App from '@apps/shell/src/app';
import {
  generateBankingServicePGClientScript,
  generateDCSScriptElement,
  generateHotjarScript,
  generateInterfaceInitializerScript,
  generateReactQueryScriptElement,
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
  generateMountRemoteSafelyFnScriptElement,
  generateSplitzExperimentsScriptElement,
  generateRazorAnalyticsScriptElement,
  generateXRemoteUrlsScript,
} from '@apps/shell/src/server/services/generator';
import { ShellServerProvider } from '@apps/shell/src/server/contexts/ShellServerProvider';
import { IS_PRODUCTION } from '@apps/shell/src/env';
import { getAppsBaseAssetUrl } from '../../utils/getAppsAssetUrl';
import { ShellError } from '@apps/shell/src/server/utils/error-utils';
import { shellFetch } from '@apps/shell/src/server/services/shellFetch';

type AppLocals = {
  user: any;
  org: any;
  clientTemplate: string;
  server_evaluated_experiments?: Record<string, any>;
};

export const generateOneDashboardTemplate = async (
  req: Request,
  appLocals: AppLocals,
): Promise<string> => {
  const requestURL = req.path;

  req.shellLogger.info({
    message: `Shell client's stats meta fetch started...`,
    moduleName: '@generateOneDashboardTemplate',
  });

  const shellClientsStatsMetaUrl = `${getAppsBaseAssetUrl('shell')}/shell.loadable-stats.json`;

  const shellStatsMetaPromise = await shellFetch(shellClientsStatsMetaUrl);

  const shellStatsMeta = await shellStatsMetaPromise.json();

  if (shellStatsMeta) {
    req.shellLogger.success({
      message: `Shell client's stats meta fetched.`,
      moduleName: '@generateOneDashboardTemplate',
    });
  } else {
    throw new ShellError({
      moduleName: '@generateOneDashboardTemplate',
      message: `Failed to fetch shell client's stats meta.${
        Boolean(shellStatsMetaPromise?.statusText)
          ? ` @statusText:${shellStatsMetaPromise.statusText}`
          : ''
      }`,
      statusCode: shellStatsMetaPromise.status,
    });
  }

  const extractor = new ChunkExtractor({
    stats: shellStatsMeta,
  });

  const isConfirmed = appLocals.user?.user?.confirmed;
  const isMobileConfirmed = appLocals.user?.user?.contact_mobile_verified;

  const requestOrigin = req.query.auth_source;
  const isRequestOriginViaWebsite = requestOrigin === 'website';
  const isRequestOriginViaWebsiteHompage = requestOrigin === 'website_homepage';

  // TODO:
  // pre signup data was already there in user object,
  // no need to fetch https://dashboard.dev.razorpay.in/merchant/details separately. verify this again.
  const isPreSignupComplete = appLocals.user?.pre_signup_complete;
  const preSignupData = appLocals.user?.pre_signup;

  // fetching query for navigation as navigation is common
  // in future scope fetch this api based on route config

  try {
    await fetchNavigation(queryClient, {});
  } catch (err) {
    console.error('Error while fetching navigation data', err);
  }

  const dehydratedState = dehydrate(queryClient, { shouldDehydrateQuery: () => true });
  const helmetContext: HelmetContext = {};

  const app = extractor.collectChunks(
    <ShellServerProvider
      dehydratedState={dehydratedState}
      helmetContext={helmetContext}
      queryClient={queryClient}
      requestURL={requestURL}
    >
      <App />
    </ShellServerProvider>,
  );
  const scriptTags = [
    generateEnvironmentKeysGetterScriptElement(),
    generateMountRemoteSafelyFnScriptElement(),
    ...extractor.getScriptElements(),
    generateReactQueryScriptElement(dehydratedState),
    generateRzpUserScriptElement(appLocals.user),
    generateSplitzExperimentsScriptElement(appLocals),
    generateDCSScriptElement(),
    generateBankingServicePGClientScript(appLocals.clientTemplate),
    generateInterfaceInitializerScript(),
    generateAnalyticsScriptElement(),
    generateSignUpRedirectScript(),
    getGoogleAPIScriptElement(),
    generateAnalyticsBundleScriptElement(),
    generateHolidayScriptElement(),
    generateAnalyticsInitializationScriptElement(),
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

  if (appLocals.clientTemplate === 'one-dashboard') {
    scriptTags.push(generateXRemoteUrlsScript());
  }

  if (IS_PRODUCTION) {
    [generateHotjarScript(), generateBladeCoverageScriptElement()].forEach((script) =>
      scriptTags.push(script),
    );
  }

  const appLinkTags = extractor.getLinkElements().map((linkTag) => {
    return (linkTag.key as string).endsWith('.js')
      ? {
          ...linkTag,
          props: {
            ...linkTag.props,
            rel: 'modulepreload',
          },
        }
      : linkTag;
  });

  const linkTags = [
    ...appLinkTags,
    ...beMiscellaneousLinkElements({
      isConfirmed,
      isMobileConfirmed,
      isRequestOriginViaWebsite,
      isRequestOriginViaWebsiteHompage,
      entryMode: 'one-dashboard',
    }),
  ];

  const sheet = new ServerStyleSheet();
  // serializing the app populates helmetContext with all head meta information
  // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
  const serializedApp = renderToString(sheet.collectStyles(app));
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
              // module-nomodule pattern for legacy and modern browsers support
              // <script src="/build/browser/js/module.js" type="module"></script>
              scriptTags={scriptTagsWithTypeModule}
              linkTags={linkTags}
              styleTags={styleTags}
              helmetContext={helmetContext}
            >
              {serializedApp}
            </Html>,
          )}`;
};
