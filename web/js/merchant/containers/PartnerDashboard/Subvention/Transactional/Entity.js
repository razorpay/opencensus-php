import { Component } from 'react';
import { withRouter } from 'react-router-dom';
import CommissionTransactionalEntity, {
  CommissionEarningBreakUp,
} from '../../Commissions/Transactional/Entity';

@withRouter
export default class SubventionTransactionalEntity extends Component {
  render() {
    return (
      <CommissionTransactionalEntity
        renderDetails={renderDetails}
        {...this.props}
      />
    );
  }
}

function renderDetails(entity) {
  return (
    <CommissionEarningBreakUp
      currency={entity.currency}
      total={entity.fee}
      gst={entity.tax}
      base={entity.fee - entity.tax}
    />
  );
}
