import React from 'react';

import { action } from '@storybook/addon-actions';
import { ContentWithoutSDK, ContentWithSDK } from './ErrorBoundary';
const errorStack = {
  componentStack: `
  trace@file:///C:/example.html:9:17
  b@file:///C:/example.html:16:13
  a@file:///C:/example.html:19:13
  @file:///C:/example.html:21:9`,
};

export default {
  title: 'ErrorBoundary',
};

export const ErrorBoundary = () => (
  <ContentWithSDK lastEventId="1234" showReportDialog={action('Report Button Clicked')} />
);

ErrorBoundary.story = {
  name: 'With SDK',
};

export const ErrorBoundaryWithoutSDK = () => (
  <ContentWithoutSDK error={new Error('something went wrong')} info={errorStack} />
);

ErrorBoundaryWithoutSDK.story = {
  name: 'Without SDK',
};
