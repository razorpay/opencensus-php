import React, { useEffect, useState } from 'react';
import { Alert, Button, Text, TextInput } from '@razorpay/blade/components';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';

import { analyticsTrack } from 'common/utils/analytics';
import { getCommonAnalyticsProperties } from 'common/utils/rzp-utils';
import { IPaymentPagesCategory } from 'merchant/reducers/paymentPages/storefront';
import { ICategory } from 'merchant/reducers/paymentPages/types';
import {
  addStorefrontCategory,
  updateStorefrontCategory,
} from 'merchant/views/PaymentPages/PaymentPages/model';
import PaymentPagesDrawer from 'merchant/views/PaymentPages/common/Drawer';
import { showNotification } from 'merchant_common/reducers/notifications';

import { CATEGORY_MESSAGES } from './constants';
import { SubHeading } from './styled';
import { validateCategory } from './utils';
import track from 'merchant/views/PaymentPages/PaymentPages/List/track';

const ADD_DATA = {
  heading: 'Add new category',
  subHeading: 'Arrange products on your storefront pages by category',
  primaryCTAText: 'Add category',
  successMessage: CATEGORY_MESSAGES.ADD,
};

const EDIT_DATA = {
  heading: 'Edit category',
  subHeading: '',
  primaryCTAText: 'Save changes',
  successMessage: CATEGORY_MESSAGES.UPDATE,
};
interface ICategoryDrawer {
  storeFrontId: string | undefined;
  isCreate: boolean;
  screenSource: 'listing_view' | 'store_view';
  categoryData: ICategory;
  handleClose: () => void;
  allCategories: IPaymentPagesCategory[];
  onChange?: ({ name, value }) => void;
  name: string;
  showNotification: ({ type, message }) => void;
  drawerPosition: 'left' | 'right';
  hasTransparentBackground: boolean;
  onSuccess: (data) => void;
  showSavedAcrossAlert?: boolean;
  top: string;
}

const CategoryDrawer = ({
  isCreate,
  storeFrontId,
  screenSource,
  categoryData,
  handleClose,
  allCategories,
  name,
  onChange,
  showNotification,
  drawerPosition,
  hasTransparentBackground,
  onSuccess,
  showSavedAcrossAlert,
  top = '45px',
}: ICategoryDrawer): React.ReactElement => {
  const [value, setValue] = useState('');
  const [isAdding, setIsAdding] = useState(false);
  const [error, setError] = useState('');

  const isEdit = !!categoryData;
  const { heading, subHeading, primaryCTAText, successMessage } = isEdit ? EDIT_DATA : ADD_DATA;

  useEffect(() => {
    track.handleAddNewCategoryLoaded({
      storefrontId: storeFrontId,
      isNewStorefront: Boolean(isCreate),
      screenSource,
    });
  }, []);

  useEffect(() => {
    if (!!categoryData) {
      setValue(categoryData.name);
    }
  }, [categoryData]);

  const handleChange = (e) => {
    setValue(e.value);
    setError('');
  };

  const onSubmit = async () => {
    const { error, isValid } = validateCategory(allCategories, value);

    if (!isValid) {
      setError(error);
      return;
    }

    setIsAdding(true);

    try {
      let response;
      if (isEdit) {
        response = await updateStorefrontCategory(categoryData.id, { name: value });
      } else {
        // tracking only add category events
        track.addCategoryClicked({
          storeFrontId,
          isNewStorefront: Boolean(isCreate),
          screenSource,
        });
        response = await addStorefrontCategory(value);
      }

      if (response.success && response.data) {
        onSuccess(response.data);
        if (onChange) onChange({ name, value: response.data.id });
        setIsAdding(false);

        showNotification({
          type: 'success',
          message: successMessage,
        });

        handleClose();
      } else {
        setIsAdding(false);
      }
    } catch (err) {
      setIsAdding(false);
      showNotification({
        type: 'error',
        message: (err as any)?.errors?.[0] || 'Something went wrong',
      });
    }
  };

  const handleEnteredAnalytics = (e) => {
    const extraproperties = {
      value_entered: e.value,
      storefrontId: storeFrontId,
      isNewStorefront: Boolean(isCreate),
      screenSource,
    };
    track.handleAddNewCategoryEntered(extraproperties);
  };

  const footerButtons = [
    <Button size="medium" type="button" variant="secondary" key="cancel" onClick={handleClose}>
      Cancel
    </Button>,
    <Button
      size="medium"
      type="button"
      variant="primary"
      key="submit"
      onClick={onSubmit}
      isDisabled={value.length === 0}
      isLoading={isAdding}
    >
      {primaryCTAText}
    </Button>,
  ];
  return (
    <PaymentPagesDrawer
      maskClosable={false}
      onClose={handleClose}
      footerButtons={footerButtons}
      position={drawerPosition}
      hasTransparentBackground={hasTransparentBackground}
      top={top}
    >
      <Text size="large">{heading}</Text>
      {subHeading && <SubHeading>{subHeading}</SubHeading>}
      <TextInput
        label="Category name"
        labelPosition="top"
        maxCharacters={100}
        name="category"
        necessityIndicator="required"
        onChange={handleChange}
        value={value}
        placeholder="Add Category name"
        validationState={error ? 'error' : 'none'}
        isRequired
        errorText={error}
        onBlur={handleEnteredAnalytics}
      />
      {isEdit && showSavedAcrossAlert && (
        <>
          <br />
          <Alert
            emphasis="subtle"
            description="Changes will be saved across all payment pages that use this category"
            isDismissible={false}
            color="notice"
          />
        </>
      )}
    </PaymentPagesDrawer>
  );
};

const mapDispatchToProps = (dispatch) => ({
  showNotification: bindActionCreators(showNotification, dispatch),
});

export default connect(null, mapDispatchToProps)(CategoryDrawer);
