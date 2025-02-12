import { Component } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import { withRouter } from 'common/deprecated/withRouter';
import ModalHeader from 'common/ui/ModalHeader';
import { fetchRules, fetchRule } from 'merchant/reducers/navigator/details';
import { closeModal } from 'merchant_common/reducers/modals';

import { RulesTable } from './RulesTable';
import { getRuleStatus } from './util';

export class ReorderRules extends Component {
  state = {
    sequence: null,
  };

  render() {
    const RULES = this.props.rules.filter((r) => getRuleStatus(r) == 'live' || r.current);
    return (
      <div className="deactivate-rule-modal">
        <ModalHeader
          title="Set Rule Priority"
          onCloseClick={() => {
            if (this.props.onClose) {
              this.props.onClose();
            }
          }}
        />

        <div className="modal-body" style={{ paddingTop: '5px' }}>
          <p className="m-b" style={{ marginBottom: '16px' }}>
            Optimizer processes your payments via rules as per the priority defined.
          </p>
          <RulesTable
            rules={RULES}
            onReorder={(rules) => {
              this.setState({ sequence: rules });
            }}
          />
        </div>
        <div className="modal-footer">
          <button
            disabled={this.props.loading}
            className="btn btn-primary"
            onClick={() => {
              if (this.props.onSuccess) {
                this.props.onSuccess(this.state.sequence ? this.state.sequence : RULES);
              }
            }}
          >
            {this.props.loading ? 'Reordering...' : 'Publish Now'}
          </button>
        </div>
      </div>
    );
  }
}

export default compose(
  withRouter,
  connect(
    (state) => {
      return {
        loading: state.navigator.reorder_loading,
      };
    },
    {
      fetchRule,
      closeModal,
      fetchRules,
    },
  ),
)(ReorderRules);
