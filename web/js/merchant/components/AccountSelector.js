import React from 'react';
import { connect } from 'react-redux';
import { TypeAhead } from 'react-power-select';

import debounce from 'common/utils/debounce';
import { titleCase } from 'common/utils/rzp-utils';
import { compose } from 'redux';
import { fetchAccountsApi, fetchAccounts } from 'merchant/reducers/marketplace/accounts';

class AccountSelector extends React.Component {
  state = {
    accountsList: null,
  };

  typeAheadSkin = React.createRef();

  componentDidMount() {
    this.props.fetchAccounts({});
  }

  handleSelect = ({ option }) => {
    this.typeAheadSkin.classList.remove('hide');

    // For display purpose only in TypeAhead
    this.props.updateAccount(option);
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
    const { props } = this;

    let accountsList;

    if (!props.accounts.loading) {
      accountsList = this.state.accountsList || props.accounts.accounts;
    }

    return (
      <>
        <Label required={props.required} text={props.label} />
        <div className="custom-select auto-complete-search" style={{ position: 'relative' }}>
          <TypeAhead
            showClear={props.showClear}
            options={accountsList}
            disabled={!accountsList}
            className="ps-in-modal"
            searchIndices={['id', 'name', 'email']}
            placeholder={`${
              !accountsList ? 'Loading...' : 'Search by account ID, name, email address'
            }`}
            selected={props.selectedAccount}
            selectedOptionLabelPath="name"
            optionComponent={({ option }) => {
              return (
                <div className="custom-powerselect-options">
                  <div>
                    <b>{titleCase(option.name)}</b> ({option.code || option.id})
                  </div>
                  {option.email}
                </div>
              );
            }}
            beforeOptionsComponent={() => <div className="heading">Recent</div>}
            onChange={this.handleSelect}
            onKeyDown={this.handleKeyDown}
          />
          <div className="typeAheadSkin" ref={(cmp) => (this.typeAheadSkin = cmp)}>
            {props.selectedAccount ? (
              <div>
                <b className="option-title">{props.selectedAccount.name}</b>
                <span> - {props.selectedAccount.id} </span>
              </div>
            ) : null}
          </div>
        </div>
      </>
    );
  }
}

function Label({ text, required }) {
  var classes = typeof required !== 'undefined' ? 'label-required' : '';

  return (
    <div className="pair-label">
      <label className={classes}>{text}</label>
    </div>
  );
}

export default compose(connect((state) => ({ accounts: state.accounts }), { fetchAccounts }))(
  AccountSelector,
);
