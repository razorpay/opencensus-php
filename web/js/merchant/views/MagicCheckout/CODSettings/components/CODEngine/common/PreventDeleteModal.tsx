import React from 'react';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import styled from 'styled-components';

import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';
import { closeModal } from 'merchant_common/reducers/modals';

const Header = styled.div`
  display: flex;
  align-items: center;
  i {
    margin-right: 8px;
    color: #d79d49;
  }
`;

const PreventDelete = ({ closeModal }) => {
  return (
    <ConfirmationModal
      header={
        <Header>
          <i className="i i-warning-o" />
          COD Settings
        </Header>
      }
      desc="Atleast one fee rule and zone is required for configuring COD settings. Toggle COD settings off if you wish to disable Magic COD"
      affirmativeLabel="Close"
      onAffirm={closeModal}
    />
  );
};

const mapDispatchToProps = (dispatch) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(PreventDelete);
