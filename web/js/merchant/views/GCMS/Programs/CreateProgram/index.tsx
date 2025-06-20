import React, { useState, useMemo, useRef, useEffect } from 'react';
import { withFormik } from 'formik';
import { sortedUniq } from 'lodash';

import ProgramDetails from 'merchant/views/GCMS/Programs/CreateProgram/Screens/Details';
import GiftCardConfiguration from 'merchant/views/GCMS/Programs/CreateProgram/Screens/GiftCardConfiguration';
import GiftCardDesign from 'merchant/views/GCMS/Programs/CreateProgram/Screens/GiftCardDesign';
import GiftCardReview from 'merchant/views/GCMS/Programs/CreateProgram/Screens/Review';
import Wizard from 'merchant/views/GCMS/Programs/CreateProgram/FormWizard';

import { useMutation } from '@tanstack/react-query';
import { useToast } from '@razorpay/blade/components';
import { addGCFile, createProgram, patchProgram } from '../queries';
import { captureDivAsImage } from '../../shared/utils';
import {
  MODE,
  FILE_UPLOAD_OPTIONS,
  isGiftCardDesignEnabled,
  DEFAULT_GIFT_CARD_LENGTH,
} from 'merchant/views/GCMS/Programs/CreateProgram/constants';
import { ProgramPriceType } from 'merchant/views/GCMS/Programs/types';
import {
  validateCardType,
  validateDenominationType,
  validateDenominationValues,
  validateDescription,
  validateDiscount,
  validateExpiryPeriod,
  validateImage,
  validateName,
  validateNotNull,
  validatePin,
  validatePrefix,
} from './validations';
import { createFileObject, translateToFormData } from './utils';
import GiftCardNumber from './Screens/GiftCardNumber';
import { useStore } from '@apps/shell/src/client/store/commonStore';

const errorValidators = {
  name: validateName,
  description: validateDescription,
  discount: validateDiscount,
  denomination_type: validateDenominationType,
  pin: validatePin,
  denomination_values: validateDenominationValues,
  validity_quantity: validateExpiryPeriod,
  validity_span: validateNotNull,
  image: validateImage,
  prefix: validatePrefix,
  card_type: validateCardType,
};

function CreateProgram({
  setFieldTouched,
  setFieldValue,
  values,
  onClose,
  submitText,
  errors,
  touched,
  editMode,
  program,
  refetch,
  setErrors,
}) {
  const session = useStore((state) => state.session);
  const mode = session.mode;
  const merchantId = session?.user?.current;
  const { show } = useToast();
  const imageRef = useRef();
  const { mutateAsync: createProgramMutation, isLoading } = useMutation({
    mutationFn: async ({ values, merchantId, mode, logo }) => {
      try {
        if (editMode === MODE.EDIT) {
          let logoFileId = null,
            imageFileId = null;

          if (isGiftCardDesignEnabled && logo && !values.url) {
            logoFileId = await addGCFile({
              file: logo,
              merchantId,
              mode,
              programId: program.id,
              prefix: 'image',
            });
          }

          if (isGiftCardDesignEnabled && !values.url) {
            imageFileId = await addGCFile({
              file: values.image,
              merchantId,
              mode,
              programId: program.id,
              prefix: 'logo',
            });
          }
          await patchProgram({
            formData: { ...values, image: imageFileId, logo: logoFileId },
            merchantId,
            mode,
            urlUpdate: isGiftCardDesignEnabled ? !values.url : false,
            programId: program.id,
          });
          return;
        }

        let logoFileId = null,
          imageFileId = null;
        if (isGiftCardDesignEnabled) {
          if (logo) {
            logoFileId = await addGCFile({ file: logo, merchantId, mode });
          }
          imageFileId = await addGCFile({ file: values.image, merchantId, mode });
        }

        await createProgram({
          formData: { ...values, image: imageFileId, logo: logoFileId },
          merchantId,
          mode,
        });
      } catch (err) {
        console.error(err);
        throw err;
      }
    },
    onSuccess: () => {
      setTimeout(() => {
        show({
          color: 'positive',
          type: 'informational',
          content:
            editMode === MODE.EDIT
              ? 'Program updated Successfully.'
              : 'Program Created Succesfully.',
        });
      }, 500);
      refetch();

      onClose();
    },
    onError: () => {
      show({
        color: 'negative',
        type: 'informational',
        content: 'Program Creation Failed!',
      });
    },
  });

  const [fixed_denomination_options, setFixedDenominationOptions] = useState([
    '100',
    '500',
    '1000',
  ]);

  function handleFormError(name, value, passAllValues = false) {
    if (!(name in errorValidators)) {
      return;
    }
    const error = passAllValues
      ? errorValidators[name]({ ...values, [name]: value })
      : errorValidators[name](value);
    const newErrors = { ...errors };
    newErrors[name] = error;
    if (!error) {
      delete newErrors[name];
    }
    setErrors(newErrors);
  }

  function handleFormChange(name, value, isTouched = true) {
    setFieldTouched(name, isTouched);
    setFieldValue(name, value);
    handleFormError(name, value, name === 'denomination_values' ? true : false);
  }

  async function onSubmit() {
    if (values.upload_type === FILE_UPLOAD_OPTIONS.BRAND_DESIGN.value) {
      if (values.url) {
        await createProgramMutation({ merchantId, values, mode, logo: undefined });
      } else {
        captureDivAsImage(imageRef, async (file) => {
          await createProgramMutation({ merchantId, values, mode, logo: file });
        });
      }
    } else {
      await createProgramMutation({ merchantId, values, mode });
    }
  }
  function addFixedDenominationOptions(value) {
    let denominationOptions = [...fixed_denomination_options];
    denominationOptions.push(value);
    denominationOptions = denominationOptions
      .map((val) => parseInt(val, 10))
      .sort((a, b) => a - b)
      .map((val) => `${val}`);
    setFixedDenominationOptions(denominationOptions);
    handleFormChange(
      'denomination_values',
      values.denomination_values.length
        ? [...values.denomination_values, value].sort((a, b) => parseInt(a) - parseInt(b))
        : [value],
    );
  }

  function initFixedDenominationOptions() {
    const {
      policies: { gift_card_price_denominations },
    } = program;
    let denominationOptions = [...fixed_denomination_options];
    denominationOptions.push(...gift_card_price_denominations.map((val) => String(val)));
    denominationOptions = denominationOptions
      .map((val) => parseInt(val, 10))
      .sort((a, b) => a - b)
      .map((val) => `${val}`);
    denominationOptions = sortedUniq(denominationOptions);

    setFixedDenominationOptions(denominationOptions);
    handleFormChange(
      'denomation_values',
      gift_card_price_denominations.map((val) => String(val)),
    );
  }

  useEffect(async () => {
    if (editMode === MODE.EDIT) {
      const { url } = program;
      const image = await createFileObject();

      if (program.policies.gift_card_price_type === ProgramPriceType.FIXED) {
        initFixedDenominationOptions();
      }
      handleFormChange('image', image);
    }
  }, []);

  const tabsData = useMemo(() => {
    let tabs = [
      {
        name: {
          boldText: 'Program',
          regularText: 'Details',
        },
        helpText: 'Provide essential details to configure your program',
        centerAlign: true,
        fields: ['name', 'description', 'discount'],
        render: () => (
          <ProgramDetails
            errors={errors}
            onChange={handleFormChange}
            values={values}
            touched={touched}
            editMode={editMode}
          />
        ),
      },

      {
        name: {
          boldText: 'Gift Card',
          regularText: 'Details',
        },
        helpText: 'Set up the details for your gift cards',
        fields: [
          'denomination_values',
          'denomination_type',
          'pin',
          'validity_quantity',
          'validity_span',
        ],
        render: () => (
          <GiftCardConfiguration
            errors={errors}
            onChange={handleFormChange}
            values={values}
            touched={touched}
            fixedDenominationOptions={fixed_denomination_options}
            addFixedDenominationOptions={addFixedDenominationOptions}
          />
        ),
      },
      {
        name: {
          boldText: 'Gift Card',
          regularText: 'Configuration',
        },
        panelText: '',
        helpText: 'Enter details to be offered for this gift card program',
        centerAlign: true,
        fields: ['card_type', 'prefix'],
        render: () => (
          <GiftCardNumber
            errors={errors}
            onChange={handleFormChange}
            values={values}
            touched={touched}
          />
        ),
      },

      {
        name: {
          boldText: 'Review',
          regularText: '& Create',
        },
        fields: [],
        helpText: 'Review and submit the configuration to create a new program',
        render: () => <GiftCardReview values={values} ref={imageRef} />,
      },
    ];
    if (isGiftCardDesignEnabled) {
      tabs.splice(3, 0, {
        name: {
          boldText: 'Gift Card',
          regularText: 'Design',
        },
        helpText: 'Choose how your gift card should look',
        fields: ['image'],
        customRender: true,
        render: () => (
          <GiftCardDesign values={values} errors={errors} onChange={handleFormChange} />
        ),
      });
    }
    return tabs;
  }, [values, errors, touched, fixed_denomination_options]);

  return (
    <Wizard
      tabsData={tabsData}
      onClose={onClose}
      isLoading={isLoading}
      onSubmit={onSubmit}
      errors={errors}
      allValid={editMode === 2 ? true : false}
      submitText={submitText}
    />
  );
}
export default withFormik({
  mapPropsToValues: ({ program, editMode }) => {
    if (editMode === MODE.EDIT) return translateToFormData(program);

    return { card_length: DEFAULT_GIFT_CARD_LENGTH };
  },
  mapPropsToTouched: ({ program, editMode }) => {
    return editMode === MODE.EDIT
      ? {
          name: true,
          description: true,
          discount: true,
          denomination_type: true,
          denomination_values: true,
          pin: true,
          steps_to_redeem: true,
          terms_and_conditions: true,
          validity_quantity: true,
          validity_span: true,
          card_type: true,
          prefix: true,
          card_length: true,
          // image: false,
          // brand_color: false,
        }
      : {};
  },
  mapPropsToErrors: ({ editMode }) => {
    return editMode === MODE.EDIT
      ? {}
      : {
          name: validateName(undefined),
          description: validateDescription(undefined),
          discount: validateDiscount(undefined),
          denomination_type: validateDenominationType(undefined),
          pin: validatePin(undefined),
          denomination_values: validateDenominationValues({}),
          validity_quantity: validateExpiryPeriod(undefined),
          validity_span: validateNotNull(undefined),
          // image: isGiftCardDesignEnabled ? validateImage(undefined) : true,
          card_type: validateCardType(undefined),
        };
  },

  validateOnChange: false,
  validateOnBlur: false,
})(CreateProgram);
