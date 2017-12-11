import React from 'react';
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
  <div>
    <b style={{ marginRight: '5px' }}>{account.name}</b>
    <span>- {account.id}</span>
    {!hideTagInfo && <CustomTag tag={account.tag} tagIcon={account.tagIcon} />}
  </div>
);

const AccountsList = ({ accounts, selectedAccount, onChange }) => {
  let typeAheadSkin = null;

  return (
    <div className="custom-select" style={{ position: 'relative' }}>
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
          <div style={{ padding: 5 }}>
            <AccountItem account={option} />
          </div>
        )}
        selectedOptionComponent={({ option }) => (
          <AccountItem account={option} hideTagInfo={true} />
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
