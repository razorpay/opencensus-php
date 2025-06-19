import React, { Component } from 'react';
import {
  Box,
  PlusCircleIcon,
  ActionListItem,
  ActionListItemIcon,
  Button,
  ActionList,
} from '@razorpay/blade/components';
import { PowerSelect } from 'react-power-select';
import { useSplitzService, withSplitzService } from 'common/splitz';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { isExperimentActive } from 'common/utils/rzp-utils';

import { createNewAccountTrack } from '@libs/shared-utils';

const CREATE_MERCHANT_CTA_LABEL = 'Create a new account';
const EXISTING_ACCOUNT_LABEL = 'Existing Account';

const SwitchMerchant = ({ user, onSwitchMerchant }) => {
  const {
    abExperiments: { create_merchant_cta },
  } = useSplitzService();

  const isCreateMerchantCTAEnabled = isExperimentActive(create_merchant_cta);

  let merchants = user.merchants;
  merchants = Object.keys(merchants).map((merchantId) => merchants[merchantId]);
  const linkedActs = merchants.flatMap((merchant) => (merchant.parent_id ? merchant : []));
  linkedActs.sort((m1, m2) =>
    (m1.display_name || m1.name).localeCompare(m2.display_name || m2.name),
  );
  let merchantsWithoutNameCount = 1;
  merchants = merchants
    .flatMap((merchant) => (merchant.parent_id ? [] : merchant))
    .concat(linkedActs)
    .map((merchant) => {
      if (!merchant.name) {
        merchant.name = `${EXISTING_ACCOUNT_LABEL} ${merchantsWithoutNameCount}`;
        merchantsWithoutNameCount++;
      }
      return merchant;
    });

  const handleCreateNewAccount = () => {
    createNewAccountTrack();
    window.open(`${window.RAZORPAY_ACCOUNTS_URL}/merchants/new`, '_blank');
  };

  return (
    <PowerSelect
      options={merchants}
      className="switch-merchant"
      placeholder="Switch Merchant"
      searchIndices={['name', 'display_name', 'billing_label']}
      showClear={false}
      optionComponent={({ option }) => {
        const { id, parent_id, display_name, name, parent_name, billing_label } = option;
        const displayName = user?.isSubMerchantDBANameEnabled
          ? billing_label
          : display_name || name;
        return (
          <span className="help-content">
            <span className="SwitchMerchantDropdown__option">
              {id === window.rzp_user.current ? (
                <i className="i i-check text-success pull-right" />
              ) : null}
              {`${parent_id ? `${parent_name} - ` : ''}${displayName}`}
              <Popover align="left" theme="dark" parentQuerySelector=".switch-merchant__Tether">
                <PopoverBody>
                  <div>{displayName}</div>
                  <div className="text--secondary">({name})</div>
                </PopoverBody>
              </Popover>
            </span>
          </span>
        );
      }}
      afterOptionsComponent={
        isCreateMerchantCTAEnabled
          ? () => {
              return (
                <ActionList>
                  <ActionListItem
                    leading={<ActionListItemIcon icon={PlusCircleIcon} />}
                    title={CREATE_MERCHANT_CTA_LABEL}
                    isSelected={true}
                    onClick={handleCreateNewAccount}
                  />
                </ActionList>
              );
            }
          : undefined
      }
      onChange={({ option }) => {
        if (option) {
          onSwitchMerchant(option);
        }
      }}
    />
  );
};

class SwitchMerchantTypeaheadComponent extends Component {
  constructor(props) {
    super(props);

    this.merchantList = Object.keys(props.user.merchants);
    let merchantsWithoutNameCount = 1;
    this.merchants = this.merchantList.reduce((acc, merchantID) => {
      const merchant = { ...props.user.merchants[merchantID] };
      if (!merchant.name) {
        merchant.name = `${EXISTING_ACCOUNT_LABEL} ${merchantsWithoutNameCount}`;
        merchantsWithoutNameCount++;
      }
      acc[merchantID] = merchant;
      return acc;
    }, {});

    this.state = {
      searchTerm: '',
      merchantIds: this.merchantList,
    };

    this.onSearch = this.onSearch.bind(this);
  }

  onSearch(e) {
    const { user } = this.props;
    const searchTerm = e.target.value;

    if (searchTerm === this.state.searchTerm) {
      return;
    }

    const merchantIds = this.merchantList.filter((item) => {
      const name = user?.isSubMerchantDBANameEnabled
        ? this.merchants[item]?.billing_label?.toLowerCase()
        : this.merchants[item].name.toLowerCase();

      return name.indexOf(searchTerm.toLowerCase()) >= 0;
    });

    this.setState({ searchTerm, merchantIds });
  }

  handleCreateNewAccount() {
    createNewAccountTrack();
    window.open(`${window.RAZORPAY_ACCOUNTS_URL}/merchants/new`, '_blank');
  }

  render() {
    const { user, onSwitchMerchant, splitz } = this.props;
    const {
      abExperiments: { create_merchant_cta },
    } = splitz;

    const isCreateMerchantCTAEnabled = isExperimentActive(create_merchant_cta);

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
          {this.state.merchantIds.map((item) => {
            const isActive = item === user.current;

            return (
              <li key={item} className={`${isActive ? 'active' : ''}`}>
                <a onClick={() => onSwitchMerchant(this.merchants[item])}>
                  <i className="i i-check" />{' '}
                  {user?.isSubMerchantDBANameEnabled
                    ? this.merchants[item].billing_label
                    : this.merchants[item].display_name || this.merchants[item].name}
                </a>
              </li>
            );
          })}
        </ul>
        {isCreateMerchantCTAEnabled ? (
          <Box marginTop={'spacing.2'}>
            <Button
              size="small"
              variant="secondary"
              iconPosition="left"
              icon={PlusCircleIcon}
              isFullWidth
              onClick={this.handleCreateNewAccount}
            >
              {CREATE_MERCHANT_CTA_LABEL}
            </Button>
          </Box>
        ) : null}
      </div>
    );
  }
}

export const SwitchMerchantTypeahead = withSplitzService(SwitchMerchantTypeaheadComponent);
export { CREATE_MERCHANT_CTA_LABEL };
export default SwitchMerchant;
