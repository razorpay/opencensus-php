import { Component, Fragment } from 'react';
import ModalHeader from 'common/ui/ModalHeader';
import { RulesTable } from './RulesTable';
import { connect } from 'react-redux';
import { closeModal } from 'merchant_common/reducers/modals';

@connect(
  (state) => {
    return {
      loading: state.navigator.deactivate_loading,
    };
  },
  {
    closeModal,
  },
)
export default class DeactivateRule extends Component {
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
      <Fragment>
        <div class="deactivate-rule-modal">
          <ModalHeader
            onCloseClick={this.props.closeModal}
            title={
              <div>
                <i className="i i-warning-o rule-deactivate-warning" />
                Deactivate a rule to publish
              </div>
            }
          />
          <div class="modal-body" style={{ paddingBottom: 0, paddingTop: 0 }}>
            <Fragment>
              <p class="m-b" style={{ marginTop: '5px' }}>
                You already have maximum (15) custom rules. Deactivate one of the rules to proceed.{' '}
                <a class="nav-link">Learn More </a>
              </p>
              <RulesTable
                radio={true}
                rules={this.props.rules}
                select={(r) => this.setState({ selected_rule: r })}
              />
            </Fragment>
          </div>
          <div className="modal-footer">
            <button
              class="btn btn-primary"
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
      </Fragment>
    );
  }
}
