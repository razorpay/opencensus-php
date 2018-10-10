import React, { Component } from 'react';
import { ModalContent } from 'component/Modal';
import { SwitchField } from 'ui/Field';
import { adminFetch, adminPatch } from 'common/fetch';
import Button from '../../component/Button';
import { openModal, closeModal, confirm } from 'common/modal';
import AsyncButton from 'ui/AsyncButton';

export default class EditBanksModal extends Component {
  constructor(props) {
    super(props);
    this.state = {
      terminalBanksMapping: {},
      enabledBanksList: [],
      disabledBanksList: [],
      loading: true,
      fetchingBanks: true,
    };
    this.fetchTermialBanks = this.fetchTermialBanks.bind(this);
    this.updateBanks = this.updateBanks.bind(this);
    this.parseData = this.parseData.bind(this);
    this.getBankField = this.getBankField.bind(this);
    this.handleChange = this.handleChange.bind(this);
  }

  componentWillMount() {
    this.fetchTermialBanks().then(data => {
      if (data) {
        this.parseData(data);
      } else {
        this.setState({ fetchingBanks: false });
      }
    });
  }

  fetchTermialBanks() {
    return adminFetch(`${this.props.mode}/terminals/${this.props.id}/banks`);
  }

  parseData(data) {
    let terminalBanksMapping = {};
    let disabledBanksList = [],
      enabledBanksList = [];

    for (let key in data.disabled) {
      terminalBanksMapping[key] = {
        name: data.disabled[key],
        value: '0',
      };
      disabledBanksList.push(key);
    }
    disabledBanksList.sort();

    for (let key in data.enabled) {
      terminalBanksMapping[key] = {
        name: data.enabled[key],
        value: '1',
      };
      enabledBanksList.push(key);
    }
    enabledBanksList.sort();

    this.props.updateEntity({
      enabled_banks: enabledBanksList,
    });
    this.setState({
      terminalBanksMapping,
      enabledBanksList,
      disabledBanksList,
      loading: false,
      fetchingBanks: false,
    });
  }

  handleChange(event) {
    let newTerminalBanksMapping = this.state.terminalBanksMapping;

    newTerminalBanksMapping[event.target.name].value = event.target.value;
    this.setState({
      terminalBanksMapping: newTerminalBanksMapping,
    });
  }

  updateBanks() {
    let newEnabledBanks = [];

    Object.keys(this.state.terminalBanksMapping).forEach(key => {
      if (this.state.terminalBanksMapping[key].value === '1') {
        newEnabledBanks.push(key);
      }
    });

    this.setState({ loading: true });
    adminPatch({
      url: `${this.props.mode}/terminals/${this.props.id}/banks`,
      data: {
        enabled_banks: newEnabledBanks,
      },
    }).then(data => {
      if (data) {
        this.parseData(data);
      } else {
        this.setState({ loading: false });
      }
    });
  }

  getBankField(list) {
    let bankList = [];

    if (list.length) {
      list.forEach(bank => {
        bankList.push(
          <SwitchField
            key={bank}
            name={bank}
            disabledLabel={this.state.terminalBanksMapping[bank].name}
            defaultValue={this.state.terminalBanksMapping[bank].value}
            nocaption
            onChange={this.handleChange}
            disabled={this.state.loading}
          />
        );
      });
    } else {
      bankList = (
        <center>
          {' '}
          <h4>No items.</h4>{' '}
        </center>
      );
    }
    return bankList;
  }

  render() {
    let enabledBanks = this.getBankField(this.state.enabledBanksList);
    let disabledBanks = this.getBankField(this.state.disabledBanksList);

    return (
      <ModalContent header="Edit Banks" class="edit-terminal-banks-modal">
        {this.state.fetchingBanks ? (
          <div class="spinner center" />
        ) : (
          <div class="row">
            <div class="form-fields">
              <div class="enabled-column">
                <div class="container">
                  <div className="heading">
                    <strong>Enabled Banks</strong>
                  </div>
                  <div class={`banks-list ${this.state.loading && 'disabled'}`}>
                    {enabledBanks}
                  </div>
                </div>
              </div>
              <div class="disabled-column">
                <div class="container">
                  <div className="heading">
                    <strong>Disabled Banks</strong>
                  </div>
                  <div class={`banks-list ${this.state.loading && 'disabled'}`}>
                    {disabledBanks}
                  </div>
                </div>
              </div>
            </div>
            <div class="form-actions">
              <button class="btn btn-default" onClick={closeModal}>
                Close
              </button>
              <AsyncButton
                text="Save"
                class="btn"
                disabled={
                  Object.keys(this.state.terminalBanksMapping).length == 0
                }
                pendingClass="small spinner"
                onSubmit={this.updateBanks}
              />
            </div>
          </div>
        )}
      </ModalContent>
    );
  }
}
