import { Link } from 'react-router-dom';
import Dropdown, { DropdownTrigger, DropdownContent } from 'rzp/ui/Dropdown';
import { PowerSelect } from 'react-power-select';
import ShowWhen from 'merchant/components/ShowWhen';
import ProfileDropdown from 'merchant/containers/Header/ProfileDropdown';

const ModesDropdown = ({ mode, modeFormatted, onSwitchMode }) => {
  return (
    <Dropdown>
      <DropdownTrigger class="dropdown-toggle">
        <span
          class={`ModeIndicator ${
            mode === 'test' ? 'ModeIndicator--test' : 'ModeIndicator--live'
          }`}
        />{' '}
        {modeFormatted} Mode <span class="caret" />
      </DropdownTrigger>
      <DropdownContent>
        <ul class="dropdown-menu">
          <li>
            <a onClick={() => onSwitchMode('test')}>Test Mode</a>
          </li>
          <li>
            <a onClick={() => onSwitchMode('live')}>Live Mode</a>
          </li>
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
      showClear={false}
      optionComponent={({ option }) => {
        return (
          <a class="SwitchMerchantDropdown__option">
            {option.id === user.current ? (
              <i class="i i-done text-success pull-right" />
            ) : null}
            <span>{option.name}</span>
          </a>
        );
      }}
      onChange={({ option, select }) => {
        if (option) {
          onSwitchMerchant(option);
        }
      }}
    />
  );
};

export default ({
  user,
  mode,
  showGSTModal,
  modeFormatted,
  onSwitchMode,
  onSwitchMerchant,
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
            <span class="i-bar" />
            <span class="i-bar" />
            <span class="i-bar" />
          </button>
        </div>
        <div
          class={`${showMobileNav ? '' : 'collapse '}navbar-collapse`}
          id="headerNav"
        >
          <ul class="nav navbar-nav navbar-right">
            <ShowWhen myRole="owner finance">
              <li>
                <a onClick={showGSTModal}>GST Details</a>
              </li>
            </ShowWhen>
            <li>
              <ModesDropdown
                mode={mode}
                modeFormatted={modeFormatted}
                onSwitchMode={onSwitchMode}
              />
            </li>
            {Object.keys(user.merchants).length > 1 ? (
              <li class="SwitchMerchantDropdown">
                <SwitchMerchant
                  user={user}
                  onSwitchMerchant={onSwitchMerchant}
                />
              </li>
            ) : null}
            <li>
              <a target="_blank" href="https://docs.razorpay.com">
                <span>Documentation</span>
              </a>
            </li>
            <li id="profile-dropdown">
              <ProfileDropdown />
            </li>
          </ul>
        </div>
      </div>
    </nav>
  );
};
