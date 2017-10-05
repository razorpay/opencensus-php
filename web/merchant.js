import React from 'react';
import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';

import App from 'merchant/App';

render(
  <Router>
    <App />
  </Router>,
  document.getElementById('react-root')
);
