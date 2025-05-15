import React from 'react';
import { Box, Text, Badge, Divider } from '@razorpay/blade/components';
import { ChannelIconMap, ChannelSourceMap, ValidFiltersWidgetTypes } from './utils';
import { FiltersWidgetProps } from './types';


export const FiltersWidget: React.FC<FiltersWidgetProps> = ({
  component,
  sourceChannel,
  storeId,
}) => {
  const componentWidget = component.filter(widget =>
    ValidFiltersWidgetTypes.includes(widget.type)
  );

  return (
    <>
      {componentWidget.map((component, index) => {
        const isLast =
          index === componentWidget.length - 1 || storeId.length === 0;
        const { title, type, inputs } = component;
        const hasInputs = inputs && inputs.length > 0;
        const isHierarchy = type === 'hierarchy_level';
        const shouldRender = hasInputs || (isHierarchy && storeId.length > 0);

        if (!shouldRender) return null;

        return (
          <Box key={component.id} display="flex" alignItems="center" gap="4px">
            <Text weight="medium" color="surface.text.gray.subtle">
              {title}
            </Text>
            {hasInputs && (
              <Badge
                color="neutral"
                size="large"
                marginLeft="spacing.3"
                icon={
                  ChannelIconMap[
                    (sourceChannel ?? inputs[0]?.default_value) as string
                  ]
                }
              >
                {
                  ChannelSourceMap[sourceChannel as string] ??
                  ChannelSourceMap[inputs[0]?.default_value as string]
                }
              </Badge>
            )}
            {isHierarchy && storeId.length > 0 && (
              <Box display="flex" flexWrap="wrap">
                {(() => {
                  const MAX_BADGES = 4;
                  const visibleItems = storeId.slice(0, MAX_BADGES);
                  const remainingCount = storeId.length - MAX_BADGES;

                  return (
                    <>
                      {visibleItems.map((item, idx) => (
                        <Badge
                          key={idx}
                          color="neutral"
                          size="large"
                          marginLeft="spacing.3"
                        >
                          {item}
                        </Badge>
                      ))}
                      {remainingCount > 0 && (
                        <Badge
                          color="neutral"
                          size="large"
                          marginLeft="spacing.3"
                        >
                          +{remainingCount} more
                        </Badge>
                      )}
                    </>
                  );
                })()}
              </Box>
            )}
            {!isLast && (
              <Divider
                orientation="vertical"
                height="24px"
                marginLeft="spacing.4"
              />
            )}
          </Box>
        );
      })}
    </>
  );
};
