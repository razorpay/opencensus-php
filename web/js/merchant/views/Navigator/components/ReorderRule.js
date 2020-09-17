import { Component, Fragment } from 'react';
import { withRouter } from 'react-router-dom';
import * as dragula from 'react-dragula';
import { rule } from './CreateRule';
import ModalHeader from 'common/ui/ModalHeader';
import { getValue, getRuleStatus } from './util';
import Popover, { PopoverBody } from 'common/ui/Popover';
import { RulesTable } from './RulesTable';
import { connect } from 'react-redux';
import { fetchRules, fetchRule } from 'merchant/reducers/navigator/details';
import { closeModal } from 'merchant_common/reducers/modals';
import Spinner from 'common/ui/Spinner';

@connect(
  (state) => {
    return {
      loading: state.navigator.reorder_loading,
    };
  },
  {
    fetchRule: fetchRule,
    closeModal: closeModal,
    fetchRules: fetchRules,
  },
)
export class ReorderRules extends Component {
  state = {
    sequence: null,
  };

  render() {
    const RULES = this.props.rules.filter((r) => getRuleStatus(r) == 'live' || r.current);
    return (
      <Fragment>
        <div class="deactivate-rule-modal">
          <ModalHeader
            title="Set Rule Priority"
            onCloseClick={() => {
              if (this.props.onClose) {
                this.props.onClose();
              }
            }}
          />

          <div class="modal-body" style={{ paddingTop: '5px' }}>
            <Fragment>
              <p class="m-b" style={{ marginBottom: '16px' }}>
                Optimizer processes your payments via rules as per the priority defined.
              </p>
              <RulesTable
                rules={RULES}
                onReorder={(rules) => {
                  this.setState({ sequence: rules });
                }}
              />
            </Fragment>
          </div>
          <div className="modal-footer">
            <button
              disabled={this.props.loading}
              class="btn btn-primary"
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
      </Fragment>
    );
  }
}

export default withRouter(ReorderRules);
