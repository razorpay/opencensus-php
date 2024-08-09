import React, { useState } from 'react';
import { connect } from 'react-redux';
import { compose } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';

import { RulesTable } from './RulesTable';
import { getRuleStatus } from 'merchant/views/Optimizer/utils';
import { RuleGroup } from 'merchant/views/Optimizer/types';

interface ReorderRulesProps {
  ruleGroups: RuleGroup[];
  loading: boolean;
  onClose?: () => void;
  onSuccess: (rule: RuleGroup[]) => void;
}

const ReorderRules = ({
  ruleGroups,
  loading,
  onClose,
  onSuccess,
}: ReorderRulesProps): JSX.Element => {
  const [sequence, setSequence] = useState<RuleGroup[]>([]);

  const rulesList = ruleGroups.filter(
    (ruleGroup) => getRuleStatus(ruleGroup) == 'live' || ruleGroup.current,
  );

  return (
    <div className="deactivate-rule-modal">
      <ModalHeader
        title="Set Rule Priority"
        onCloseClick={() => {
          if (onClose) {
            onClose();
          }
        }}
      />

      <div className="modal-body" style={{ paddingTop: '5px' }}>
        <p className="m-b" style={{ marginBottom: '16px' }}>
          Optimizer processes your payments via rules as per the priority defined.
        </p>
        <RulesTable
          ruleGroups={rulesList}
          onReorder={(rules) => {
            setSequence(rules);
          }}
        />
      </div>
      <div className="modal-footer">
        <button
          disabled={loading}
          className="btn btn-primary"
          onClick={() => {
            if (onSuccess) {
              onSuccess(sequence.length > 0 ? sequence : rulesList);
            }
          }}
        >
          {loading ? 'Reordering...' : 'Publish Now'}
        </button>
      </div>
    </div>
  );
};

const mapStateToProps = (state) => {
  const { navigator } = state;
  return {
    loading: navigator.reorder_loading,
  };
};

export default compose<any>(connect(mapStateToProps, null))(ReorderRules);
