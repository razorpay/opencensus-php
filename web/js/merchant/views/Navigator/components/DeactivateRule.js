import { Component } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';

import { RulesTable } from './RulesTable';

class DeactivateRule extends Component {
  static contextTypes = {
    confirm: PropTypes.func,
  };
  state = {
    selected_rule: null,
  };

  deactivateRule = (rule) => {
    if (this.props.onSuccess) {
      this.props.onSuccess(rule);
    }
  };

  render() {
    return (
      <div className="deactivate-rule-modal">
        <ModalHeader
          onCloseClick={this.props.closeModal}
          title={
            <div>
              <i className="i i-warning-o rule-deactivate-warning" />
              Deactivate a rule to publish
            </div>
          }
        />
        <div className="modal-body" style={{ paddingBottom: 0, paddingTop: 0 }}>
          <p className="m-b" style={{ marginTop: '5px' }}>
            You already have maximum (15) custom rules. Deactivate one of the rules to proceed.{' '}
            <a className="nav-link">Learn More </a>
          </p>
          <RulesTable
            radio={true}
            rules={this.props.rules}
            select={(r) => this.setState({ selected_rule: r })}
          />
        </div>
        <div className="modal-footer">
          <button
            className="btn btn-primary"
            onClick={() => {
              this.context
                .confirm({
                  header: 'Are you sure you want to deactivate?',
                  message: `The rule will not be deleted. it'll be deactivated and is saved in drafts.`,
                  affirmativeLabel: 'Confirm',
                  abortLabel: 'Cancel',
                })
                .then(() => {
                  return this.deactivateRule(this.state.selected_rule);
                });
            }}
            disabled={!this.state.selected_rule || this.props.loading}
          >
            {!this.props.loading ? 'Deactivate Rule' : 'Deactivating..'}
          </button>
        </div>
      </div>
    );
  }
}

export default connect(
  (state) => {
    return {
      loading: state.navigator.deactivate_loading,
    };
  },
  {
    closeModal,
  },
)(DeactivateRule);
