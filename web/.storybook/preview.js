import React from 'react';
import { addDecorator } from '@storybook/react';
import { addParameters } from '@storybook/client-api';
import { INITIAL_VIEWPORTS } from '@storybook/addon-viewport';
import Wrapper from '../v2/components/Bootstrap/Wrapper';
import { Router, Route } from 'react-router-dom';
import { createMemoryHistory } from 'history';

if (typeof global.process === 'undefined') {
  const { worker } = require('../mocks/browser');
  worker.start();
}

addParameters({
  viewport: {
    viewports: INITIAL_VIEWPORTS, // newViewports would be an ViewportMap. (see below for examples)
    defaultViewport: 'galaxys5',
  },
});
addDecorator((story) => (
  <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
    <Route path="/" component={() => story()} />
  </Router>
));
addDecorator((StoryFn) => (
  <Wrapper context={{ mode: 'test', org: {}, user: {} }}>{<StoryFn />}</Wrapper>
));

//export const decorators = [addDecorator];

//export const parameters = { layout: 'fullscreen' };
