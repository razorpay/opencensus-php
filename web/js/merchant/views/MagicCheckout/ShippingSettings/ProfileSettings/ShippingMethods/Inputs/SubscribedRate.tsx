import React, { useState } from 'react';
import { Box, CloseIcon, IconButton, Link, Text } from '@razorpay/blade/components';

import Input from 'common/new-ui/Input';
import SwitchField from 'common/ui/Forms/SwitchField';
import { useFormContext } from 'merchant/views/MagicCheckout/ShippingSettings/ProfileSettings/ShippingMethods/FormContext';
import { SettingsWrapper } from 'merchant/views/MagicCheckout/ShippingSettings/styles';

import Label from './Label';

type Tag = {
  tag: string;
  fee: number;
};

const DEFAULT_TAG: Tag = { tag: '', fee: 0 };

const SubscribedRate = (): JSX.Element => {
  const { values, setValue } = useFormContext();
  const formTagsEntries = Object.entries(values.attribute_rules.value?.customer_tags);
  const formTags = formTagsEntries.length
    ? formTagsEntries.map((entry) => ({ tag: entry[0], fee: entry[1] } as Tag))
    : [{ ...DEFAULT_TAG }];
  const [tags, setTags] = useState<Tag[]>(formTags);
  const [isEnabled, setIsEnabled] = useState(formTagsEntries.length > 0);

  const updateFormValues = () => {
    const newTags = {};
    tags.forEach((tag) => {
      newTags[tag.tag] = +tag.fee;
    });
    setValue('attribute_rules', {
      customer_tags: {
        ...newTags,
      },
    });
  };

  const handleTagDelete = (index) => {
    if (tags.length > 1) {
      tags.splice(index, 1);
      setTags([...tags]);
      updateFormValues();
    }
  };

  const handleAddMore = () => {
    setTags([...tags, { ...DEFAULT_TAG }]);
  };

  const handleChange = (e, key) => {
    const {
      dataset: { arrInd },
    } = e.target;
    const val = e.target.value;
    tags[arrInd][key] = val;
    setTags([...tags]);
    updateFormValues();
  };

  const handleEnableChange = (checked) => {
    setIsEnabled(checked);
    if (!checked) {
      setValue('attribute_rules', {
        customer_tags: {},
      });
    } else {
      updateFormValues();
    }
  };

  return (
    <>
      <Label required={false} value="Subscribed Rate" error={values.attribute_rules.error} />
      <Box flex="1">
        <Box marginBottom="spacing.5" display="flex" gap="spacing.5" alignItems="center">
          <SwitchField
            onChange={handleEnableChange}
            checked={isEnabled}
            defaultChecked={isEnabled}
            type="prime"
          />
          {isEnabled ? (
            <b className="text-primary toggle-status">Enabled</b>
          ) : (
            <b className="text-faded toggle-status">Disabled</b>
          )}
        </Box>
        {isEnabled ? (
          <SettingsWrapper>
            {tags.map((tag, index) => (
              <Box key={index} gap="spacing.5">
                <Box display="flex" gap="spacing.3">
                  <Box marginBottom="spacing.4">
                    <Text size="small" marginBottom="spacing.3" weight="semibold">
                      Name
                    </Text>
                    <Input
                      onChange={(e) => handleChange(e, 'tag')}
                      value={tag.tag}
                      data-arr-ind={index}
                      type="text"
                      name="tag"
                    />
                  </Box>
                  <Box marginLeft="spacing.8">
                    <Text size="small" marginBottom="spacing.3" weight="semibold">
                      Rate
                    </Text>
                    <Input
                      onChange={(e) => handleChange(e, 'fee')}
                      value={tag.fee}
                      data-arr-ind={index}
                      addonBefore="₹"
                      type="number"
                      name="value"
                    />
                  </Box>
                  <IconButton
                    accessibilityLabel="delete"
                    onClick={() => handleTagDelete(index)}
                    icon={() => (
                      <CloseIcon
                        margin="spacing.4"
                        size="medium"
                        color="feedback.icon.negative.intense"
                      />
                    )}
                  />
                </Box>
              </Box>
            ))}
            <Link onClick={handleAddMore} variant="button">
              + Add more
            </Link>
          </SettingsWrapper>
        ) : null}
      </Box>
    </>
  );
};

export default SubscribedRate;
