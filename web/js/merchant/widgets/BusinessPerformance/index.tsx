import { useMobile } from '@libs/shared-utils';
import { Box, Heading } from '@razorpay/blade/components';
import React from 'react';

import { CommonWidgetProps } from 'merchant/widgets/types';
import { renderInput } from '../common/utils';
import { useRetryWidget } from '../hooks';
import { VARIANT_TO_ICON_MAP } from './constants';
import { getPropsForInput } from './helpers';
import { Icon } from './Icon';
import { Loader } from './Loader';
import { PerformanceCard } from './PerformaceCard';
import { BusinessPerformanceProps } from './types';

export const BusinessPerformance = (props: BusinessPerformanceProps & CommonWidgetProps) => {
  const isMobile = useMobile();
  const [isRetrying, retryHandler] = useRetryWidget(props.queryKey);
  const { date_time } = props.filters;

  function handleChange({ values }: { values: string }) {
    retryHandler({ id: props.id, filter_selected: values[0], date_time });
  }

  const isLoading = props.isLoading || isRetrying;

  return (
    <Box backgroundColor="surface.background.gray.intense" padding="spacing.7">
      <Box display="flex" justifyContent="space-between">
        <Heading>{props.title}</Heading>

        {props.inputs.map((input) => {
          const widgetProps = getPropsForInput({ isMobile, input });

          return (
            <React.Fragment key={input.name}>
              {renderInput({ onChange: handleChange, widget: { ...input, ...widgetProps } })}
            </React.Fragment>
          );
        })}
      </Box>

      {isLoading ? (
        <Loader />
      ) : (
        <>
          {props.components.map((component) => {
            return (
              <Box key={component.id} marginTop="spacing.8">
                <Box display="flex" alignItems="center" gap="spacing.3">
                  <Icon name={VARIANT_TO_ICON_MAP[component.variant]} variant={component.variant} />
                  <Heading>{component.title}</Heading>
                </Box>
                <Box
                  display="grid"
                  gap="spacing.5"
                  gridTemplateColumns={
                    isMobile ? undefined : 'repeat(auto-fit, minmax(210px, 1fr));'
                  }
                  marginTop="spacing.5"
                >
                  {component.data.cards.map((item, index) => (
                    <PerformanceCard
                      key={item.label}
                      item={item}
                      index={index + 1}
                      variant={component.variant}
                      isMobile={isMobile}
                    />
                  ))}
                </Box>
              </Box>
            );
          })}
        </>
      )}
    </Box>
  );
};
