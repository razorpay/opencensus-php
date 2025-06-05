import React from 'react';
import { AlertTriangleIcon, Box, Button, Card, Text } from '@razorpay/blade/components';
import { useNavigate } from 'react-router-dom';
import useOneHomeAnalytics from '../../hooks/useOneHomeAnalytics';
import { criticalSectionMessages } from './constants';
import useCriticalActions from './hooks/useCriticalActions';
import {
  Action,
  ActionParamsInternal,
  CriticalActionComponent,
  ActionParamsExternal,
} from './types';

export const CriticalActionCardButton: React.FC<{
  actions: Action[];
  alias: string;
}> = ({ actions, alias }) => {
  const navigate = useNavigate();
  const { criticalActionDataLength } = useCriticalActions();
  const { trackOneHomeAnalytics } = useOneHomeAnalytics();

  const trackCTA = ({ title }: Action) => {
    const properties = {
      title: 'Critical Actions',
      itemName: title,
      widgetId: criticalSectionMessages.widgetId,
      subWidgetId: `one_home_${alias}`,
      criticalActionsCount: criticalActionDataLength,
    };
    trackOneHomeAnalytics({
      objectName: 'Ucs Link',
      actionName: 'Clicked',
      properties,
    });
  };

  const handleCTA = (actionParams: ActionParamsInternal | ActionParamsExternal) => {
    if ('path' in actionParams) {
      navigate(actionParams.path);
    } else if ('url' in actionParams) {
      window.open(actionParams.url, '_blank');
    }
  };

  return (
    <>
      {actions.map((item: Action, index) => {
        if (item.action === 'navigate') {
          return (
            <Box key={index} maxWidth="fit-content">
              <Button
                variant={item.properties?.variant}
                color="primary"
                size="small"
                onClick={() => {
                  handleCTA(item.action_params);
                  trackCTA(item);
                }}
                type={item.type}
              >
                {item.title}
              </Button>
            </Box>
          );
        }
        return null;
      })}
    </>
  );
};

const CriticalActionCard: React.FC<CriticalActionComponent> = ({
  title,
  description,
  alias,
  actions,
  error,
}: CriticalActionComponent) => {
  if (error) {
    throw error;
  }

  return (
    <Card
      width="100%"
      padding="spacing.0"
      display="flex"
      elevation="none"
      borderRadius="large"
      backgroundColor="surface.background.gray.intense"
    >
      <Box
        display="flex"
        flexDirection="column"
        padding="spacing.5"
        flexGrow="1"
        whiteSpace="normal"
      >
        <Box
          width="100%"
          display="flex"
          flexDirection={{
            base: 'column',
            m: 'row',
          }}
          gap={{
            base: 'spacing.1',
            m: 'spacing.3',
          }}
          alignItems={{ base: 'flex-start', m: 'baseline' }}
        >
            <AlertTriangleIcon color="feedback.icon.negative.intense" alignSelf="start" size="xlarge" />

            <Text size="large" weight="semibold" color="surface.text.gray.normal">
              {title}
            </Text>
        </Box>

        <Box
          minWidth="248px"
          marginLeft={{
            m: 'spacing.8',
          }}
          display="flex"
          flexDirection="column"
          gap={{ base: 'spacing.4', m: 'spacing.5' }}
          justifyContent="space-between"
          flexGrow="1"
        >
          <Text size="medium" weight="medium" color="surface.text.gray.muted">
            {description}
          </Text>

          <CriticalActionCardButton actions={actions ?? []} alias={alias} />
        </Box>
      </Box>
    </Card>
  );
};

export default CriticalActionCard;
