import React, { Fragment } from 'react';
import { AlertTriangleIcon, Box, Divider, Text } from '@razorpay/blade/components';
import { CriticalActionCardButton } from './CriticalActionCard';
import { CriticalActionInDrawerProps } from './types';

const CriticalActionsInDrawer = ({ criticalActioncomponent }: CriticalActionInDrawerProps) => {
  if (criticalActioncomponent?.error) {
    throw criticalActioncomponent?.error;
  }

  return (
    <Fragment key={criticalActioncomponent.id}>
      <Box display="flex" flexDirection="column" paddingY="spacing.3">
        <Box
          width="100%"
          display="flex"
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          gap={{
            base: 'spacing.3',
            m: 'spacing.5',
          }}
          alignItems={{ base: 'flex-start', m: 'baseline' }}
        >
          <Box width="spacing.5" height="spacing.5">
            <AlertTriangleIcon color="feedback.icon.negative.intense" alignSelf="start" />
          </Box>

          <Box>
            <Text size="large" weight="semibold" color="surface.text.gray.normal">
              {criticalActioncomponent.title}
            </Text>
          </Box>
        </Box>

        <Box
          width="100%"
          minWidth="248px"
          marginLeft={{
            m: 'spacing.8',
          }}
          display="flex"
          flexDirection="column"
          gap="spacing.5"
          justifyContent="space-between"
        >
          <Box>
            <Text size="medium" weight="medium" color="surface.text.gray.muted">
              {criticalActioncomponent.description}
            </Text>
          </Box>

          <CriticalActionCardButton
            actions={criticalActioncomponent.actions ?? []}
            alias={criticalActioncomponent.alias}
          />
        </Box>
      </Box>

      <Box marginY="spacing.5">
        <Divider width="380px" height="0.5px" orientation="horizontal" dividerStyle="solid" />
      </Box>
    </Fragment>
  );
};

export default CriticalActionsInDrawer;
