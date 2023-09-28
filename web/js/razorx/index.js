/* eslint-disable */
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import css from '../../css/razorx.styl';
import fontconfig from '../../dashboard.font';
import { redirectToAppRoute } from 'common/utils/redirectToAppRoute';
import App from './App';
__webpack_public_path__ = `${window.cdnDashboardUrl || ''}/dist/`;

redirectToAppRoute('/razorx');

render(
  <Router basename="/razorx">
    <App />
  </Router>,
  document.getElementById('react-root'),
);
