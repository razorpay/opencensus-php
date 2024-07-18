import React, { Fragment, useState } from 'react';
import { connect } from 'react-redux';
import { compose, bindActionCreators } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import { closeModal } from 'merchant_common/reducers/modals';
import { TOTAL_RULE_LIMIT } from 'merchant/views/Optimizer/utils';
import { RuleGroup } from 'merchant/views/Optimizer/types';

import { RulesTable } from './RulesTable';

interface DeactivateRuleProps {
  ruleGroups: RuleGroup[];
  loading: boolean;
  onSuccess: (rule: any) => void;
  closeModal?: () => void;
}

const DeactivateRule = (
  { ruleGroups, loading, onSuccess, closeModal }: DeactivateRuleProps,
  context,
): JSX.Element => {
  const [selectedRule, setSelectedRule] = useState<RuleGroup>();

  const deactivateRule = () => {
    context
      .confirm({
        header: 'Are you sure you want to deactivate?',
        message: `The rule will not be deleted. it'll be deactivated and is saved in drafts.`,
        affirmativeLabel: 'Confirm',
        abortLabel: 'Cancel',
      })
      .then(() => {
        if (onSuccess) {
          onSuccess(selectedRule);
        }
      });
  };

  return (
    <Fragment>
      <div className="deactivate-rule-modal">
        <ModalHeader
          onCloseClick={closeModal}
          title={
            <div>
              <i className="i i-warning-o rule-deactivate-warning" />
              Deactivate a rule to publish
            </div>
          }
        />
        <div className="modal-body" style={{ paddingBottom: 0, paddingTop: 0 }}>
          <Fragment>
            <p className="m-b" style={{ marginTop: '5px' }}>
              You already have maximum ({TOTAL_RULE_LIMIT}) custom rules. Deactivate one of the
              rules to proceed.
            </p>
            <RulesTable radio={true} ruleGroups={ruleGroups} select={(r) => setSelectedRule(r)} />
          </Fragment>
        </div>
        <div className="modal-footer">
          <button
            className="btn btn-primary"
            onClick={deactivateRule}
            disabled={!selectedRule || loading}
          >
            {!loading ? 'Deactivate Rule' : 'Deactivating..'}
          </button>
        </div>
      </div>
    </Fragment>
  );
};

DeactivateRule.contextTypes = {
  confirm: Function,
};

const mapStateToProps = (state) => {
  const { navigator } = state;
  return {
    loading: navigator.deactivate_loading,
  };
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );
};

export default compose<any>(connect(mapStateToProps, mapDispatchToProps))(DeactivateRule);
