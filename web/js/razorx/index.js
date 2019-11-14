import { render } from 'react-dom';
import { BrowserRouter as Router } from 'react-router-dom';
import App from './App';

render(
  <Router basename="/razorx">
    <App />
  </Router>,
  document.getElementById('react-root')
);
