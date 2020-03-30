import { Link } from 'react-router-dom';

export default ({ track }) => (
  <div class="Announcement_Banner">
    You can personalize your account to add your logo, add brand colour and do
    lots more<div class="big-dot-separator" />
    <Link to="/config" onClick={track}>
      Personalize account
    </Link>
  </div>
);
