import React from 'react';
import { render } from 'react-dom';
import { HashRouter as Router } from 'react-router-dom';

import App from 'admin/App';

render(
  <Router>
    <App />
  </Router>,
  document.getElementById('react-root')
);
