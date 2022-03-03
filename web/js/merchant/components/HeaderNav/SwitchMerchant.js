import React, { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import Popover, { PopoverBody } from 'common/ui/Popover';

const SwitchMerchant = ({ user, onSwitchMerchant }) => {
  let merchants = user.merchants;
  merchants = Object.keys(merchants).map((merchantId) => merchants[merchantId]);
  merchants = merchants.filter((merchant) => merchant.product === 'primary');
  return (
    <PowerSelect
      options={merchants}
      className="switch-merchant"
      placeholder="Switch Merchant"
      searchIndices={['name', 'display_name']}
      showClear={false}
      optionComponent={({ option }) => {
        return (
          <span class="help-content">
            <span class="SwitchMerchantDropdown__option">
              {option.id === user.current ? <i class="i i-check text-success pull-right" /> : null}
              {option.display_name || option.name}
              <Popover align="left" theme="dark" parentQuerySelector=".switch-merchant__Tether">
                <PopoverBody>
                  <div>{option.display_name || option.name}</div>
                  <div class="text--secondary">({option.name})</div>
                </PopoverBody>
              </Popover>
            </span>
          </span>
        );
      }}
      onChange={({ option }) => {
        if (option) {
          onSwitchMerchant(option);
        }
      }}
    />
  );
};

export class SwitchMerchantTypeahead extends Component {
  constructor(props) {
    super(props);

    this.merchantList = Object.keys(props.user.merchants);

    this.state = {
      searchTerm: '',
      merchants: this.merchantList,
    };

    this.onSearch = this.onSearch.bind(this);
  }

  onSearch(e) {
    const searchTerm = e.target.value;
    const { user } = this.props;

    if (searchTerm === this.state.searchTerm) {
      return;
    }

    const merchants = this.merchantList.filter((item) => {
      const name = user.merchants[item].name.toLowerCase();

      return name.indexOf(searchTerm.toLowerCase()) >= 0;
    });

    this.setState({ searchTerm, merchants });
  }

  render() {
    const { user, onSwitchMerchant } = this.props;

    return (
      <div>
        <input
          type="text"
          className="form-control"
          value={this.state.searchTerm}
          placeholder="Search"
          onChange={this.onSearch}
        />
        <ul className="merchants-list nav nav-stacked">
          {this.state.merchants.map((item) => {
            const isActive = item === user.current;

            return (
              <li key={item} className={`${isActive ? 'active' : ''}`}>
                <a onClick={() => onSwitchMerchant(user.merchants[item])}>
                  <i className="i i-check" />{' '}
                  {user.merchants[item].display_name || user.merchants[item].name}
                </a>
              </li>
            );
          })}
        </ul>
      </div>
    );
  }
}

export default SwitchMerchant;
