import React, { Fragment, useEffect } from 'react';
import { Link, MinusCircleIcon, PlusCircleIcon } from '@razorpay/blade/components';
import { useField } from 'formik';

import FormikTextInputField from 'merchant/views/StoreSettings/common/components/FormFields/FormikTextInputField';

const StoreContactDetails = () => {
  const [field, , helpers] = useField('storeContact');
  const { setValue } = helpers;
  const [isStoreContactToggled, setIsStoreContactToggled] = React.useState<boolean>(false);

  useEffect(() => {
    if (Object.values(field.value || {}).some((value) => value)) {
      setIsStoreContactToggled(true);
    }
  }, [field.value]);
  return isStoreContactToggled ? (
    <Fragment>
      <FormikTextInputField
        name="storeContact.storeInchargeName"
        label="Store Incharge Name"
        placeholder="Enter Store Incharge Name"
        labelPosition="left"
      />
      <FormikTextInputField
        name="storeContact.primaryContactNumber"
        label="Primary Contact Number"
        placeholder="Enter Primary Contact Number"
        labelPosition="left"
      />
      <FormikTextInputField
        name="storeContact.secondaryContactNumber"
        label="Alt. Contact Number"
        placeholder="Enter Alt. Contact Number"
        labelPosition="left"
      />
      <FormikTextInputField
        labelPosition="left"
        name="storeContact.emailId"
        label="Email ID"
        placeholder="Enter Email ID"
      />
      <Link
        icon={MinusCircleIcon}
        color="negative"
        onClick={() => {
          setValue({});
          setIsStoreContactToggled(false);
        }}
        variant="button"
        data-analytics-name="remove-store-contact-details"
      >
        Remove Store Contact Details
      </Link>
    </Fragment>
  ) : (
    <Link icon={PlusCircleIcon} onClick={() => setIsStoreContactToggled(true)} variant="button" data-analytics-name="add-store-contact-details">
      Add Store Contact Details
    </Link>
  );
};

export default StoreContactDetails;
