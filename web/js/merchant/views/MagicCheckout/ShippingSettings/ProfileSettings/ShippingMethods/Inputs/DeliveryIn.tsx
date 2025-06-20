import React, { useCallback } from 'react';
import Input from 'common/new-ui/Input';
import Label from './Label';
import { Box, Text, Switch } from '@razorpay/blade/components';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';

const OPTIONS = [
  {
    label: 'Days',
    name: 'days',
  },
  {
    label: 'Business Days',
    name: 'working days',
  },
  {
    label: 'Weeks',
    name: 'weeks',
  },
  {
    label: 'Hours',
    name: 'hours',
  },
];

const DeliveryIn = (): JSX.Element => {
  const { values, setValue } = useFormContext();

  React.useEffect(() => {
    // set default value for units if not exist
    if (!values.estimated_delivery_details.value.unit) {
      setETDValue('unit', 'days');
    }
  }, []);

  const setETDValue = useCallback(
    (key: string, value: string | number | boolean) => {
      setValue('estimated_delivery_details', {
        ...values.estimated_delivery_details.value,
        [key]: value,
      });
    },
    [values, setValue],
  );

  return (
    <Box>
      <Box display="flex" gap="spacing.5">
      <Label
        value="Display Delivery in"
        tooltipContent='"Estimated date of delivery on checkout“ when set to true, delivery days will be displayed for the user on selection of address. Needs the following Delivery in Min and max values to be set mandatorily'
      />
      <Box display={'flex'} gap={'1rem'} height={'36px'}>
        <Box width={'60px'}>
          <Switch
            accessibilityLabel="Toggle DarkMode"
            size="medium"
            isChecked={values.estimated_delivery_details.value.display}
            onChange={(e) => {
              setETDValue('display', e.isChecked);
            }}
          />
        </Box>
      </Box>
      </Box>
      {values.estimated_delivery_details.value.display &&
        <Box marginTop={'spacing.5'} display={'flex'}  gap={'spacing.5'}>
          <Label
            value="Delivery in"
            error={values.estimated_delivery_details.error}
            showTooltip={true}
            tooltipContent="Indicate the typical number of days it takes for a customer to receive the product once shipped using this delivery method. This will be shown on Magic to help the customer choose the shipping method."
          />
          <Box display={'flex'} gap={'1rem'} height={'36px'}>
            <Box width={'60px'}>
              <Input
                type="number"
                placeholder="Min"
                min={0}
                value={values.estimated_delivery_details.value.min_timeframe}
                onChange={(e) => {
                  if (e.target.value !== '' && !isNaN(+e.target.value) && +e.target.value >= 0) {
                    setETDValue('min_timeframe', +e.target.value);
                  } else {
                    setETDValue('min_timeframe', '');
                  }
                }}
              />
            </Box>
            <Text size="small" alignSelf="center">
              to
            </Text>
            <Box width={'60px'}>
              <Input
                type="number"
                placeholder="Max"
                value={values.estimated_delivery_details.value.max_timeframe}
                onChange={(e) => {
                  if (e.target.value !== '' && !isNaN(+e.target.value) && +e.target.value >= 0) {
                    setETDValue('max_timeframe', +e.target.value);
                  } else {
                    setETDValue('max_timeframe', '');
                  }
                }}
              />
            </Box>
            <Box width={'140px'}>
              <Input.Select
                value={values.estimated_delivery_details.value.unit}
                size="small"
                name="unit"
                options={OPTIONS}
                className="unit-select"
                onChange={(e) => {
                  setETDValue('unit', e.target.value);
                }}
              />
            </Box>
          </Box>
        </Box>
      }
    </Box>
  );
};

export default DeliveryIn;
