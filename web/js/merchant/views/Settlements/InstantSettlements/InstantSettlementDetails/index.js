import React, { Component } from 'react';
import { connect } from 'react-redux';
import Details from 'merchant/views/Settlements/InstantSettlements/InstantSettlementDetails/Details';
import * as InstantSettlementActions from 'merchant/reducers/instantSettlements/details';
import * as ModalActions from 'merchant_common/reducers/modals';
import { bindActionCreators } from 'redux';

class InstantSettlementDetails extends Component {
  UNSAFE_componentWillMount() {
    this.props.fetchItem(this.props.id);
  }

  UNSAFE_componentWillReceiveProps(nextProps) {
    if (this.props.id !== nextProps.id) {
      this.props.fetchItem(nextProps.id);
    }
  }

  render() {
    const {
      loading,
      error,
      instantSettlement,
      closeModal,
      fetchTotalSettlementAmount,
    } = this.props;
    let statusMsg = {};

    if (error) {
      statusMsg = {
        type: 'error',
        message: error,
      };
    }

    return (
      <Details
        closeModal={closeModal}
        settlement={instantSettlement}
        isLoading={loading}
        statusMsg={statusMsg}
        fetchTotalSettlementAmount={fetchTotalSettlementAmount}
      />
    );
  }
}

const mapStateToProps = (state) => state.instantSettlement;

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators({ ...InstantSettlementActions, ...ModalActions }, dispatch);
};

export default connect(mapStateToProps, mapDispatchToProps)(InstantSettlementDetails);
