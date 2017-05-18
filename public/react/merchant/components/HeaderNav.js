import { Link } from 'react-router-dom';
import Dropdown, {
  DropdownTrigger,
  DropdownContent,
} from 'react-simple-dropdown';

const ModesDropdown = ({ modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          <li><a onClick={() => onSwitchMode('test')}>Test Mode</a></li>
          <li><a onClick={() => onSwitchMode('live')}>Live Mode</a></li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

const SwitchMerchantDropdown = ({ user, onSwitchMerchant }) => {
  let merchants = user.merchants;
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        Switch Merchant <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          {Object.keys(merchants).map(merchantId => {
            let merchant = merchants[merchantId];
            return (
              <li key={merchantId}>
                <a onClick={() => onSwitchMerchant(merchant)}>
                  {merchant.id === user.current
                    ? <i class="icon icon-done text-success" />
                    : <i class="fa fa-fw" />}
                  <span>{merchant.name}</span>
                </a>
              </li>
            );
          })}
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

const ProfileDropdown = ({ user, onLogoutClick }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        {user.name || user.user.name} <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          {user.current &&
            <li>
              <Link to="/orders">
                Activation
                {' '}
                {!user.activated &&
                  <span class="badge bg-danger pull-right">
                    {user.activation_progress}%
                  </span>}
              </Link>
            </li>}
          <li><Link to="/orders">Profile</Link></li>
          <li class="divider" />
          <li><a onClick={onLogoutClick}>Logout</a></li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default ({
  user,
  modeFormatted,
  onSwitchMode,
  onSwitchMerchant,
  onLogout,
}) => {
  return (
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container-fluid">
        <div class="collapse navbar-collapse">
          <ul class="nav navbar-nav navbar-right">
            <li><a data-tip="Merchant ID" data-place="bottom">{user.id}</a></li>
            <li>
              <ModesDropdown
                modeFormatted={modeFormatted}
                onSwitchMode={onSwitchMode}
              />
            </li>
            {Object.keys(user.merchants).length > 1
              ? <li>
                  <SwitchMerchantDropdown
                    user={user}
                    onSwitchMerchant={onSwitchMerchant}
                  />
                </li>
              : null}
            <li>
              <a target="_blank" href="https://docs.razorpay.com">
                <span>Documentation</span>
              </a>
            </li>
            <li><ProfileDropdown user={user} onLogoutClick={onLogout} /></li>
          </ul>
        </div>
      </div>
    </nav>
  );
};
