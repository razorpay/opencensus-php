import React from 'react';
import debounce from 'common/utils/debounce';
import { fetchAccountsApi } from 'merchant/reducers/marketplace/accounts';
import { TypeAhead } from 'react-power-select';

const CustomTag = ({ tag, tagIcon }) => {
  if (!tag) {
    return null;
  }

  return (
    <span className={`${tagIcon ? tagIcon + ' icon' : ''} custom-tag`}>
      <span className="text">{tag}</span>
    </span>
  );
};

const AccountItem = ({ account, hideTagInfo }) => (
  <div className="rzp-account-item">
    <b className={`account-name ${account.tag ? '' : 'tag-invisible'}`}>{account.name}</b>
    <span className="account-id"> - {account.id}</span>
    {!hideTagInfo && <CustomTag tag={account.tag} tagIcon={account.tagIcon} />}
  </div>
);

const AccountItemDetailsPreview = ({ account, hideTagInfo }) => {
  return (
    <div className="rzp-account-item rzp-account-item-preview">
      <div>
        <b className={`account-name ${account.tag ? '' : 'tag-invisible'}`}>{account.name}</b>
        {account.code && `( ${account.code} )`}
        {!hideTagInfo && <CustomTag tag={account.tag} tagIcon={account.tagIcon} />}
      </div>
      <div className="id-email">
        <span className="account-id">{account.id}</span>
        <span className="dot-separator"></span>
        <span>{account.email}</span>
      </div>
    </div>
  );
};

class AccountsList extends React.Component {
  state = {
    selectedAccount: null,
  };

  handleSelect = ({ option }) => {
    this.typeAheadSkin.classList.remove('hide');

    if (typeof this.props.onChange === 'function') {
      this.props.onChange(option);
    }
  };

  searchInAccountList(val) {
    fetchAccountsApi(null, { q: val, search_hits: 1 })
      .then((resp) => {
        let accountsList = null;
        if (resp.data && resp.data.items && resp.data.items.length) {
          accountsList = resp.data.items;
        }

        this.setState({ accountsList });
      })
      .catch((err) => {
        this.setState({ accountsList: null });
      });
  }

  debounce_searchInAccountList = debounce(this.searchInAccountList.bind(this), 50);

  handleKeyDown = (e) => {
    const target = e.target;

    setTimeout(() => {
      const val = target.value;

      if (val.length < 2) {
        this.setState({ accountsList: null });
        return;
      }

      this.debounce_searchInAccountList(val);
    }, 5);
  };

  render() {
    const { accounts, selectedAccount, onChange } = this.props;

    let accountsList;
    if (!accounts.loading) {
      accountsList = this.state.accountsList || accounts;
    }

    return (
      <div className="custom-select rzp-accounts-list">
        <i className="i i-search custom-icon" />
        <div className="typeAheadSkin" ref={(c) => (this.typeAheadSkin = c)}>
          {!!selectedAccount && <AccountItem account={selectedAccount} />}
        </div>

        <TypeAhead
          options={accountsList}
          disabled={!accountsList}
          placeholder={`${
            !accountsList ? 'Loading...' : 'Account ID, Account Name, Email Address'
          }`}
          showClear={true}
          selected={selectedAccount}
          optionLabelPath="name"
          searchIndices={['name', 'id', 'email']}
          optionComponent={({ option }) => (
            <div className="rzp-option-component">
              <AccountItemDetailsPreview account={option} />
            </div>
          )}
          selectedOptionComponent={({ option }) => <AccountItem account={option} />}
          onClick={() => {
            this.typeAheadSkin.classList.add('hide');
          }}
          onChange={this.handleSelect}
          onKeyDown={this.handleKeyDown}
        />
      </div>
    );
  }
}

export default AccountsList;
