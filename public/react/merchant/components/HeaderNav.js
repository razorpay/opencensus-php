import { Link } from 'react-router-dom';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import { PowerSelect } from 'react-power-select';

const ModesDropdown = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        <span
          class={`ModeIndicator ${mode === 'test' ? 'ModeIndicator--test' : 'ModeIndicator--live'}`}
        />
        {' '}
        {modeFormatted}
        {' '}
        Mode
        {' '}
        <span class="caret" />
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

const SwitchMerchant = ({ user, onSwitchMerchant }) => {
  let merchants = user.merchants;
  merchants = Object.keys(merchants).map(merchantId => merchants[merchantId]);
  return (
    <PowerSelect
      options={merchants}
      placeholder="Switch Merchant"
      searchIndices={['name']}
      optionComponent={({ option }) => {
        return (
          <a class="SwitchMerchantDropdown__option">
            {option.id === user.current
              ? <i class="icon icon-done text-success pull-right" />
              : null}
            <span>{option.name}</span>
          </a>
        );
      }}
      onChange={(option, select) => {
        if (option) {
          onSwitchMerchant(option);
        }
      }}
    />
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
              <Link to="/activation">
                Activation
                {' '}
                {!user.activated &&
                  <span class="badge bg-danger pull-right">
                    {user.activation_progress}%
                  </span>}
              </Link>
            </li>}
          <li><Link to="/profile">Profile</Link></li>
          <li class="divider" />
          <li><a onClick={onLogoutClick}>Logout</a></li>
        </ul>
      </DropdownContent>
    </Dropdown>
  );
};

export default ({
  user,
  mode,
  modeFormatted,
  onSwitchMode,
  onSwitchMerchant,
  onLogout,
  toggleMobileNav,
  showMobileNav,
}) => {
  return (
    <nav class="navbar navbar-default navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button
            type="button"
            class="navbar-toggle"
            data-toggle="collapse"
            onClick={toggleMobileNav}
          >
            <span class="icon-bar" />
            <span class="icon-bar" />
            <span class="icon-bar" />
          </button>
        </div>
        <div
          class={`${showMobileNav ? '' : 'collapse '}navbar-collapse`}
          id="headerNav"
        >
          <ul class="nav navbar-nav navbar-right">
            <li><a data-tip="Merchant ID" data-place="bottom">{user.id}</a></li>
            <li>
              <ModesDropdown
                mode={mode}
                modeFormatted={modeFormatted}
                onSwitchMode={onSwitchMode}
              />
            </li>
            {Object.keys(user.merchants).length > 1
              ? <li class="SwitchMerchantDropdown">
                  <SwitchMerchant
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
