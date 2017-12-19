import React from 'react';
import { TypeAhead } from 'react-power-select';

import './styles.styl';

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
    <b>{account.name}</b>
    <span>- {account.id}</span>
    {!hideTagInfo && <CustomTag tag={account.tag} tagIcon={account.tagIcon} />}
  </div>
);

const AccountsList = ({ accounts, selectedAccount, onChange }) => {
  let typeAheadSkin = null;

  return (
    <div className="custom-select rzp-accounts-list">
      <i className="icon icon-search custom-icon" />
      <div
        className="typeAheadSkin"
        ref={el => {
          typeAheadSkin = el;
        }}
      >
        {!!this.selectedAccount && (
          <AccountItem account={this.selectedAccount} />
        )}
      </div>

      <TypeAhead
        options={accounts}
        placeholder="Search for merchant Name/Email/Merchant ID"
        optionLabelPath="name"
        onClick={() => {
          typeAheadSkin.classList.add('hide');
        }}
        searchIndices={['name', 'id', 'email']}
        selected={selectedAccount}
        showClear={false}
        optionComponent={({ option }) => (
          <div className="rzp-option-component">
            <AccountItem account={option} />
          </div>
        )}
        selectedOptionComponent={({ option }) => (
          <AccountItem account={option} />
        )}
        onChange={({ option }) => {
          if (option) {
            onChange(option);
            typeAheadSkin.classList.remove('hide');
          }
        }}
      />
    </div>
  );
};

export default AccountsList;
