/* eslint-disable no-unused-vars */
/* eslint-disable import/extensions */
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';

import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';

import App from './App';
import css from '../../css/razorx.styl';
import fontconfig from '../../dashboard.font';

redirectToAppRoute('/razorx');

render(
  <Router basename="/razorx">
    <App />
  </Router>,
  document.getElementById('react-root'),
);
