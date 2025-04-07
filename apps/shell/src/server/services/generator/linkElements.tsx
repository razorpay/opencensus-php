import React from 'react';
import { IS_PRODUCTION } from '@apps/shell/src/env';
import { getAppsBaseAssetUrl } from '../../utils/getAppsAssetUrl';

export const beMiscellaneousLinkElements = ({
  isConfirmed,
  isMobileConfirmed,
  isRequestOriginViaWebsite,
  isRequestOriginViaWebsiteHompage,
  entryMode,
}: {
  isConfirmed: boolean;
  isMobileConfirmed: boolean;
  isRequestOriginViaWebsite: boolean;
  isRequestOriginViaWebsiteHompage: boolean;
  entryMode: 'la-dashboard' | 'payments-dashboard' | 'one-dashboard';
}) => {
  const links: JSX.Element[] = [
    <meta charSet="utf-8" />,
    /** @ts-ignore */
    <meta name="google" value="notranslate" />,
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />,
    <meta name="author" content="Razorpay" />,
    <link rel="icon" type="image/png" href="https://razorpay.com/favicon.png" />,
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5" />,
    <meta name="clarity-site-verification" content="cc0522fd-b574-460c-8446-17f0fb19e52e" />,
    <title>Razorpay Dashboard</title>,
    <meta
      name="description"
      content="Online payment gateway for India with the best in class API, integration procedure, robust security and powerful dashboard"
    />,
    <link
      rel="preload"
      href={`${getAppsBaseAssetUrl('shell')}/shell.remoteEntry.js`}
      as="script"
    />,
  ];

  if (IS_PRODUCTION) {
    links.push(
      <link rel="dns-prefetch" href="https://static.hotjar.com" />,
      <link rel="dns-prefetch" href="https://vars.hotjar.com" />,
      <link rel="dns-prefetch" href="https://script.hotjar.com" />,
    );
  } else {
    links.push(<meta name="robots" content="noindex" />);
  }

  if (
    (isConfirmed || isMobileConfirmed) &&
    !isRequestOriginViaWebsite &&
    !isRequestOriginViaWebsiteHompage
  ) {
    links.push(
      <link
        rel="dns-prefetch"
        href="https://rzp-1415-prod-dashboard-activation.s3.amazonaws.com"
      />,
      <link rel="dns-prefetch" href="https://maxcdn.bootstrapcdn.com" />,
      <link rel="dns-prefetch" href="https://o515678.ingest.sentry.io" />,
      <link rel="dns-prefetch" href="https://www.google-analytics.com" />,
      <link rel="dns-prefetch" href="https://www.googleadservices.com" />,
      <link rel="dns-prefetch" href="https://connect.facebook.net" />,
      <link rel="dns-prefetch" href="https://www.youtube.com" />,
      <link rel="dns-prefetch" href="https://googleads.g.doubleclick.net" />,
      <link rel="dns-prefetch" href="https://www.facebook.com" />,
      <link rel="dns-prefetch" href="https://www.google.com" />,
      <link rel="dns-prefetch" href="https://www.google.co.in" />,
      // <link rel="dns-prefetch" href="https://api.refiner.io" />,
      // <link rel="dns-prefetch" href="https://js.refiner.io" />,
      <link rel="dns-prefetch" href="https://d2r1yp2w7bby2u.cloudfront.net" />,
      <link rel="dns-prefetch" href="https://cdn.segment.com" />,
      <link rel="dns-prefetch" href="https://lumberjack.razorpay.com" />,
      <link rel="preconnect" href="https://fonts.googleapis.com" />,
      <link rel="preconnect" href="https://www.gstatic.com" />,
      <link
        href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"
        as="style"
      />,
    );
  }

  return links;
};
