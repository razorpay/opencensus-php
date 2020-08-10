__webpack_public_path__ = (window.cdnDashboardUrl || '') + `/dist/`;
import 'regenerator-runtime/runtime.js';
import 'core-js/es/map';
import 'core-js/es/set';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import App from './App';
import css from '../../css/razorx.styl';
import fontconfig from '../../dashboard.font';

render(
  <Router basename="/razorx">
    <App />
  </Router>,
  document.getElementById('react-root')
);
