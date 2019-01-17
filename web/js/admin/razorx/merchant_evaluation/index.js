import { observer } from 'mobx-react';
import { withRouter } from 'react-router-dom';
import { openModal, closeModal, notifyError } from 'common/modal';
import { SwitchField } from 'ui/Field';
import List from '../experiments/List';
import Entity from '../experiments/Entity';
import { AppStore } from 'admin/user';

@withRouter
@observer
export default class MerchantEvaluation extends React.Component {
  render() {
    return (
      <div class="parent-container features-container">
        <div class="header">
          <span class="title">Merchant Evaluation</span>
          <SwitchField
            name="mode"
            defaultValue={AppStore.mode}
            disabledLabel="Test"
            enabledLabel="Live"
            enabledValue="live"
            disabledValue="test"
            onChange={AppStore.updateMode}
          />
        </div>
        <div class="container-group">
          <List />
          <Entity id={this.props.id} />
        </div>
      </div>
    );
  }
}
