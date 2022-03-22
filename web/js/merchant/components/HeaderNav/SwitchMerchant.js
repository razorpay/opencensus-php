import React, { Component } from 'react';
import { PowerSelect } from 'react-power-select';
import Popover, { PopoverBody } from 'common/ui/Popover';

const SwitchMerchant = ({ user, onSwitchMerchant }) => {
  let merchants = user.merchants;
  merchants = Object.keys(merchants).map((merchantId) => merchants[merchantId]);
  const linkedActs = merchants.flatMap((merchant) => (merchant.parent_id ? merchant : []));
  linkedActs.sort((m1, m2) =>
    (m1.display_name || m1.name).localeCompare(m2.display_name || m2.name),
  );
  merchants = merchants
    .flatMap((merchant) => (merchant.parent_id ? [] : merchant))
    .concat(linkedActs);

  return (
    <PowerSelect
      options={merchants}
      className="switch-merchant"
      placeholder="Switch Merchant"
      searchIndices={['name', 'display_name']}
      showClear={false}
      optionComponent={({ option }) => {
        const { id, parent_id, display_name, name, parent_name } = option;
        return (
          <span className="help-content">
            <span className="SwitchMerchantDropdown__option">
              {id === window.rzp_user.current ? (
                <i className="i i-check text-success pull-right" />
              ) : null}
              {`${parent_id ? `${parent_name} - ` : ''}${display_name || name}`}
              <Popover align="left" theme="dark" parentQuerySelector=".switch-merchant__Tether">
                <PopoverBody>
                  <div>{display_name || name}</div>
                  <div className="text--secondary">({name})</div>
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
