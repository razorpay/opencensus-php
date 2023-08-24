import React, { useEffect, useState } from 'react';
import { FormikValues, useFormik } from 'formik';
import { isEmpty } from 'lodash';
import { connect } from 'react-redux';
import { withRouter } from 'react-router';
import { compose, bindActionCreators } from 'redux';
import * as Yup from 'yup';

import { CommonApiResponse, FormikHandleChange, ShowNotificationType, User } from 'common/typings';
import { create as createSubmerchant } from 'merchant/reducers/submerchant';
import { Org } from 'merchant/views/PartnerDashboard/Home/TypesDeclare/home';
import { INVITE_TAB_TYPES } from 'merchant/views/PartnerDashboard/SubMerchant/components/InviteMerchantModal/components/InviteMerchantTabs/constants';
import { trackSubmerchantReferViaEmail } from 'merchant/views/PartnerDashboard/SubMerchant/utils/analytics';
import { TODO_PD } from 'merchant/views/PartnerDashboard/TypesDeclare';
import { PRODUCT_TYPE } from 'merchant/views/PartnerDashboard/constants';
import { showNotification } from 'merchant_common/reducers/notifications';

import AddMerchantForm from './AddMerchantForm';
import SuccessScreen from './SuccessScreen';
import { SINGLE_ADD_MERCHANT_STEPS } from './constants';

const { ADD_MERCHANT_FORM, SUCCESS_SCREEN } = SINGLE_ADD_MERCHANT_STEPS;
const commonValidations = {
  name: Yup.string()
    .trim()
    .matches(/^[a-zA-Z\s]+$/, {
      message: 'Name may only contain alphabets and spaces.',
      excludeEmptyString: true,
    })
    .min(4, 'Client Name should have at least 4 characters.')
    .required('Client Name is a required field.'),
};

const orgToValidationSchema = {
  rzp: Yup.object().shape({
    ...commonValidations,
    email: Yup.string()
      .email('Please enter a valid email id.')
      .required('Email ID is a required field.'),
    contact_mobile: Yup.string()
      .trim()
      .length(10, 'Please enter a valid 10-digit mobile number.')
      .nullable(),
  }),
  curlec: Yup.object().shape({
    ...commonValidations,
    // TODO v2: curlec validations
    // const countryCode = user?.merchant?.country_code || 'IN';
    email: Yup.string().email('Please enter a valid email id.').nullable(),
    contact_mobile: Yup.string()
      .trim()
      .length(10, 'Please enter a valid 10-digit mobile number.')
      .nullable(),
  }),
};

type SingleAddMerchantProps = {
  user: User;
  org: Org;
  location: { pathname: string };
  productType: string;
  setShowHeaderAndTabs: (args: boolean) => void;
  onInviteTabsBackClick: () => void;
  onDismiss: () => void;
  onAddSuccess?: () => void;
  showNotification: ShowNotificationType;
  createSubmerchant: (args: {
    name: string;
    email?: string;
    contact_mobile?: string;
    product: string;
    isInsertTable?: boolean;
  }) => Promise<CommonApiResponse<{ status: boolean }, string[]>>;
};
const SingleAddMerchant = ({
  user,
  org,
  location,
  productType,
  createSubmerchant,
  setShowHeaderAndTabs,
  onInviteTabsBackClick,
  onDismiss,
  onAddSuccess = () => {},
  showNotification,
}: SingleAddMerchantProps): JSX.Element => {
  const inviteFlow = INVITE_TAB_TYPES.SINGLE_INVITE;
  // Steps logic
  const [currentStep, setCurrentStep] = useState(ADD_MERCHANT_FORM);
  const [isSendingInvite, setIsSendingInvite] = useState(false);
  // Formik logic and Validation
  const handleFormSubmit = (params) => {
    trackSubmerchantReferViaEmail(params);
    setIsSendingInvite(true);

    const getIsInsertTable = () => {
      const isAddXIntent = productType === PRODUCT_TYPE.X;
      const isAddPGIntent = productType === PRODUCT_TYPE.PG;
      const isCurrentPageX = location?.pathname === '/partners/submerchants/x';
      const isCurrentPagePG = location?.pathname === '/partners/submerchants';
      if ((isAddXIntent && isCurrentPageX) || (isAddPGIntent && isCurrentPagePG)) {
        return true;
      }
      return false;
    };
    const handleErrorResponse = ({ errors = [] }) => {
      setIsSendingInvite(false);
      showNotification({
        type: 'error',
        message: errors?.[0] || 'Something went wrong',
      });
    };

    // TODO v2: move .then() into a utility hook
    return createSubmerchant({
      ...params,
      product: productType,
      isInsertTable: getIsInsertTable(),
    })
      .then(() => {
        setIsSendingInvite(false);
        onAddSuccess();
        // TODO v2: tracking events from hook based on productType
        if (user.isPartner('reseller')) {
          setShowHeaderAndTabs(false);
          setCurrentStep(SUCCESS_SCREEN);
        } else {
          showNotification({
            type: 'success',
            message: 'Submerchant created successfully',
          });
          onDismiss();
        }
      })
      .catch(handleErrorResponse);
  };
  const initialValues = {
    name: '',
    contact_mobile: '',
    email: '',
  };
  // Load Rzp vs Curlec validation rules
  const validationSchema = orgToValidationSchema[org?.custom_code || 'rzp'];
  const formik = useFormik<FormikValues>({
    initialValues,
    validationSchema,
    validateOnChange: true,
    validateOnBlur: false,
    onSubmit: handleFormSubmit,
  });

  // Header visibility handling
  useEffect(() => {
    setShowHeaderAndTabs(currentStep !== SUCCESS_SCREEN);
  }, [currentStep]);

  const handleChange: FormikHandleChange = ({ name, value }) => {
    if (name) {
      formik.setFieldTouched(name);
      formik.setFieldValue(name, value);
    }
  };

  const onSendInviteClick = async () => {
    const errors = await formik.validateForm();
    if (!isEmpty(errors)) {
      // TODO v2: tracking events
      return;
    }
    formik.handleSubmit();
  };
  // Footer contents
  return (
    <>
      {currentStep === ADD_MERCHANT_FORM ? (
        <AddMerchantForm
          formik={formik}
          handleChange={handleChange}
          isSendingInvite={isSendingInvite}
          onSendInviteClick={onSendInviteClick}
          onBackClick={onInviteTabsBackClick}
        />
      ) : null}
      {currentStep === SUCCESS_SCREEN ? (
        <SuccessScreen
          orgCode={org?.custom_code}
          productType={productType}
          merchantEmail={formik.values.email}
          merchantContact={formik.values.contact_mobile}
          source={inviteFlow}
          partnerID={user.id}
        />
      ) : null}
    </>
  );
};

export default compose<TODO_PD>(
  withRouter,
  connect(
    (state) => ({ user: state.session.user, org: state.session.org }),
    (dispatch) => bindActionCreators({ showNotification, createSubmerchant }, dispatch),
  ),
)(SingleAddMerchant);
