import React, { PropsWithChildren } from 'react';
import {
  Box,
  Card,
  CardBody,
  CardProps,
  Divider,
  InfoIcon,
  Tooltip,
  TooltipInteractiveWrapper,
  Heading,
} from '@razorpay/blade/components';

type InfoContainerProps = PropsWithChildren<{
  title: string;
  info?: string;
  height?: CardProps['height'];
}>;

const InfoContainer = ({
  title,
  info,
  children,
  height = '100%',
}: InfoContainerProps): React.ReactElement => {
  return (
    <Card padding="spacing.0" backgroundColor="surface.background.gray.moderate" height={height} data-analytics-name={`${title}-section`}>
      <CardBody>
        <Box
          display="flex"
          justifyContent="space-between"
          alignItems="center"
          paddingX="spacing.7"
          paddingY="spacing.4"
          maxHeight="17%"
        >
          <Heading size="medium">{title}</Heading>
          {info ? (
            <Tooltip content={info} placement="top">
              <TooltipInteractiveWrapper>
                <InfoIcon size="xlarge" color="surface.icon.gray.muted" />
              </TooltipInteractiveWrapper>
            </Tooltip>
          ) : null}
        </Box>
        {children ? (
          <>
            <Divider thickness="thinner" variant="normal" />
            {children}
          </>
        ) : null}
      </CardBody>
    </Card>
  );
};

export default InfoContainer;
