import React from 'react';
import {
  Box,
  Text,
  Link,
  Tooltip,
  TooltipInteractiveWrapper,
  CopyIcon,
  useToast,
} from '@razorpay/blade/components';

import CustomClipboard from 'common/ui/Clipboard/Custom';

type FieldInfoProps = {
  label: string;
  value: string;
  enableCopyOption?: boolean;
};

const validateUrl = (value: string) => {
  try {
    return new URL(value);
  } catch {
    return null;
  }
};

const isLinkField = (value: string) => validateUrl(value) !== null;

const FieldInfo = ({
  label,
  value,
  enableCopyOption = false,
}: FieldInfoProps): React.ReactElement => {
  const toast = useToast();

  const handleCopy = () => {
    toast.show({
      type: 'informational',
      color: 'positive',
      content: `${label} value copied.`,
    });
  };

  return (
    <Box display="flex" paddingY="spacing.4" gap="spacing.7">
      <Box width="30%">
        <Text>{label}</Text>
      </Box>
      <Box width="65%" overflow="hidden">
        {isLinkField(value) ? (
          <Tooltip content={value}>
            <TooltipInteractiveWrapper>
              <Link href={value} rel="noreferrer noopener" target="_blank">
                {value}
              </Link>
            </TooltipInteractiveWrapper>
          </Tooltip>
        ) : (
          <Box display="flex" gap="spacing.3">
            <Text weight="semibold" wordBreak="break-word">
              {value}
            </Text>
            {enableCopyOption && (
              <CustomClipboard value={value} onCopy={handleCopy}>
                <CopyIcon color="interactive.icon.primary.normal" />
              </CustomClipboard>
            )}
          </Box>
        )}
      </Box>
    </Box>
  );
};

export default FieldInfo;
