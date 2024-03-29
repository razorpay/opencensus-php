import { AlertTriangleIcon, Button, CloseIcon, IconButton } from '@razorpay/blade/components';
import React from 'react';
import {
  StyledUpdateBusinessDetailsContainer,
  StyledUpdateBusinessDetailsContent,
} from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/Styled';
import { withRouter } from 'common/deprecated/withRouter';
import type { RouteComponentProps } from 'common/deprecated/RouteComponentProps';
import { closeModal as closeModalFn } from 'merchant_common/reducers/modals';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { updateWebsitePath } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/components/Banner';
import { trackIEEvent } from 'merchant/views/AccountAndSettings/PaymentMethods/Tabs/International/components/InternationalCards/utils/track';

type Props = RouteComponentProps & {
  closeModal: () => void;
};

const UpdateBusinessDetailsModal = ({ history, closeModal }: Props) => {
  const onUpdateClick = () => {
    trackIEEvent({
      objectName: 'Update Website Details',
      actionName: 'Clicked',
      subSection: 'Update Website Details Modal',
    });
    history.push(updateWebsitePath);
    closeModal();
  };

  return (
    <StyledUpdateBusinessDetailsContainer>
      <StyledUpdateBusinessDetailsContent>
        <AlertTriangleIcon color="feedback.icon.notice.intense" size="large" />
        <div>
          <h4>Update website details</h4>
          <p>
            Your website must be registered to request for international payments on payment gateway
          </p>
        </div>
        <IconButton size="large" icon={CloseIcon} onClick={closeModal} accessibilityLabel="Close" />
      </StyledUpdateBusinessDetailsContent>
      <Button isFullWidth onClick={onUpdateClick} size="medium">
        Update
      </Button>
    </StyledUpdateBusinessDetailsContainer>
  );
};

const mapDispatchToProps = (dispatch) => {
  return bindActionCreators(
    {
      closeModal: closeModalFn,
    },
    dispatch,
  );
};

export default withRouter(connect(null, mapDispatchToProps)(UpdateBusinessDetailsModal));
