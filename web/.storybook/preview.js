import React from 'react';
import { addDecorator } from '@storybook/react';
import { addParameters } from '@storybook/client-api';
import { INITIAL_VIEWPORTS } from '@storybook/addon-viewport';
import Wrapper from '../v2/components/Bootstrap/Wrapper';
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

addDecorator((StoryFn) => <Wrapper context={{ mode: 'test', org: {} }}>{<StoryFn />}</Wrapper>);

//export const decorators = [addDecorator];
