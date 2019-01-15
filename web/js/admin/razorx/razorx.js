import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import App from './index';

render(
  <Router basename="/admin/razorx">
    <App />
  </Router>,
  document.getElementById('react-root')
);
