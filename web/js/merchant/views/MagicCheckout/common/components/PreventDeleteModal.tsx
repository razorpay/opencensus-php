import React from 'react';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

import ConfirmationModal from 'merchant/views/MagicCheckout/common/components/ConfirmationModal';

import { closeModal } from 'merchant_common/reducers/modals';
import styled from 'styled-components';

const Header = styled.div`
  display: flex;
  align-items: center;
  i {
    margin-right: 8px;
    color: #d79d49;
  }
`;

interface PreventDeleteProps {
  header: string;
  description: string;
  closeModal: () => void;
}

const PreventDelete = ({ header, description, closeModal }) => {
  return (
    <ConfirmationModal
      header={
        <Header>
          <i className="i i-warning-o" />
          {header}
        </Header>
      }
      desc={description}
      affirmativeLabel="Okay"
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

const Component: ({ header, description }: Omit<PreventDeleteProps, 'closeModal'>) => JSX.Element =
  connect(null, mapDispatchToProps)(PreventDelete);
export default Component;
