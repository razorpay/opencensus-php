import React from 'react';
import { connect } from 'react-redux';
import { AnyAction, bindActionCreators, Dispatch } from 'redux';

import ModalHeader from 'common/ui/ModalHeader';
import ClickpostForm from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/components/ClickpostForm';

import { closeModal } from 'merchant_common/reducers/modals';

import type { LinkAccountPropsType } from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/types';

import { LinkAccountContent } from 'merchant/views/MagicCheckout/ShippingServices/ClickPostAccountModal/styled';

const LinkAccountContainer = ({ closeModal }: LinkAccountPropsType): JSX.Element => {
  return (
    <div>
      <ModalHeader onCloseClick={closeModal} />
      <LinkAccountContent>
        <div className="link-account-header font-bold color-black">Link Clickpost account</div>
        <div className="link-account-desc">
          Please enter the credentials below for better RTO protection
        </div>
        <ClickpostForm />
      </LinkAccountContent>
    </div>
  );
};

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>) =>
  bindActionCreators(
    {
      closeModal,
    },
    dispatch,
  );

export default connect(null, mapDispatchToProps)(LinkAccountContainer);
