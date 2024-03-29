import React from 'react';
import { ContactDetailsWrapper, ContactDetailsHeading, ContactDetailsSubHeading } from './styled';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';
import { InfoIcon, ChevronDownIcon } from '@razorpay/blade/components';
import ContactDetailsDrawer from 'merchant/views/PaymentPages/common/Products/ContactDetailsDrawer';
import { closeModal, openModal } from 'merchant_common/reducers/modals';

interface IContactDetails {
  contactPhone: string;
  contactEmail: string;
  closeModal: () => void;
  openModal: (data: any) => void;
  isLoaded: boolean;
}

const ContactDetails = ({
  contactPhone,
  contactEmail,
  closeModal,
  openModal,
  isLoaded,
}: IContactDetails) => {
  const hasContactDetails = contactPhone && contactEmail;

  const handleOpenContactDetailsModal = () => {
    openModal({
      isNew: true,
      component: <ContactDetailsDrawer handleClose={closeModal} />,
    });
  };
  const hasError = isLoaded && !hasContactDetails;
  return (
    <ContactDetailsWrapper>
      <ContactDetailsHeading onClick={handleOpenContactDetailsModal} showError={hasError}>
        More options
        <ChevronDownIcon
          size="medium"
          color={!hasError ? 'surface.icon.gray.subtle' : 'feedback.icon.negative.intense'}
        />
      </ContactDetailsHeading>
      <ContactDetailsSubHeading showError={hasError}>
        {hasError && (
          <>
            <InfoIcon size="small" color="feedback.icon.negative.intense" />
            &nbsp;
          </>
        )}
        Contact phone/email
      </ContactDetailsSubHeading>
    </ContactDetailsWrapper>
  );
};

const mapStateToProps = (state) => ({
  contactPhone: state.paymentPageStorefront.entity.contactPhone,
  contactEmail: state.paymentPageStorefront.entity.contactEmail,
});

const mapDispatchToProps = (dispatch) => bindActionCreators({ closeModal, openModal }, dispatch);

export default connect(mapStateToProps, mapDispatchToProps)(ContactDetails);
