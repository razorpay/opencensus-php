import React, { useEffect, useState } from 'react';
import {
  PageTitleWrapper,
  StoreFrontName,
  StoreFrontentIcon,
  EditPageTitleIcon,
  EditPageTitleWrapper,
  PageTitle,
  EditButtonWrapper,
  EditPageInputWrapper,
} from './styled';
import { TextInput, Button as BladeButton, CloseIcon, CheckIcon } from '@razorpay/blade/components';
import EditIcon from 'assets/payment_pages/edit.svg';
import StorefrontIcon from 'assets/payment_pages/storefront.svg';
import { editStorefront } from 'merchant/reducers/paymentPages/storefront';
import { bindActionCreators } from 'redux';
import { connect } from 'react-redux';

interface IStorefrontPageTitle {
  title: string;
  editStorefront: (key: string, value: any) => void;
  user: {
    business_name: string;
    display_name: string;
  };
}

const StorefrontPageTitle = ({ title, editStorefront, user }: IStorefrontPageTitle) => {
  const [isEditing, setEditing] = useState(false);
  const [pageTitle, setPageTitle] = useState(title);

  const updateTitle = (e) => {
    setPageTitle(e.value);
  };

  const handleCancel = () => {
    setEditing(false);
    setPageTitle(title);
  };

  const checkIfValid = () => {
    return pageTitle.length > 0 && pageTitle.length <= 40;
  };

  const handleSave = () => {
    if (checkIfValid()) {
      setEditing(false);
      editStorefront('title', pageTitle);
    }
  };

  useEffect(() => {
    if (!title) {
      const newTitle = user?.display_name || user?.business_name || '';
      setPageTitle(newTitle);
      editStorefront('title', newTitle);
    }
  }, []);
  const isValid = checkIfValid();
  return (
    <PageTitleWrapper>
      <StoreFrontName>
        <StoreFrontentIcon src={StorefrontIcon} alt="Storefront" />
        Storefront name
      </StoreFrontName>
      {isEditing ? (
        <EditPageTitleWrapper>
          <EditPageInputWrapper>
            <TextInput
              label=""
              maxCharacters={40}
              name="pageTitle"
              onChange={updateTitle}
              value={pageTitle}
              placeholder="Add Storefront name"
              validationState={isValid ? 'none' : 'error'}
              isRequired
              autoFocus
              errorText="Please enter storefront name"
            />
          </EditPageInputWrapper>
          <EditButtonWrapper>
            <BladeButton
              size="small"
              type="button"
              onClick={handleSave}
              variant="secondary"
              icon={CheckIcon}
              isDisabled={!isValid}
              data-testID="page-title-save"
            />
            <BladeButton
              size="small"
              type="button"
              onClick={handleCancel}
              variant="tertiary"
              icon={CloseIcon}
              data-testID="page-title-cancel"
            />
          </EditButtonWrapper>
        </EditPageTitleWrapper>
      ) : (
        <PageTitle>
          <span>{title}</span>
          <EditPageTitleIcon src={EditIcon} alt="Edit" onClick={() => setEditing(true)} />
        </PageTitle>
      )}
    </PageTitleWrapper>
  );
};

const mapStateToProps = (state) => ({
  title: state.paymentPageStorefront.entity.title,
  user: state.session.user,
});

const mapDispatchToProps = (dispatch) => ({
  editStorefront: bindActionCreators(editStorefront, dispatch),
});

export default connect(mapStateToProps, mapDispatchToProps)(StorefrontPageTitle);
