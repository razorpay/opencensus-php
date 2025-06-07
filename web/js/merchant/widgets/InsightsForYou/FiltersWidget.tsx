import React from 'react';
import { Box, Text, Badge, Divider, Tooltip } from '@razorpay/blade/components';
import { ChannelIconMap, ChannelSourceMap, ValidFiltersWidgetTypes } from './utils';
import { FiltersWidgetProps, ComponentDataType } from './types';


export const FiltersWidget: React.FC<FiltersWidgetProps> = ({
  component,
  sourceChannel,
  storeId,
}) => {
  const componentWidget = component.filter(widget =>
    ValidFiltersWidgetTypes.includes(widget.type)
  ) as ComponentDataType[];

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
            <Text weight="medium" color="surface.text.gray.subtle" marginTop="3px">
              {title}
            </Text>
            {hasInputs && (
              <Badge
                color="neutral"
                size="large"
                marginLeft="spacing.3"
                marginTop="spacing.2"
                icon={
                  ChannelIconMap[
                    (sourceChannel || inputs[0]?.default_value) as string
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
                  const remainingStoresIds = storeId.slice(MAX_BADGES, storeId.length);

                  const remainingStoresNames = component?.data?.merchant_store_hierarchy?.store_hierarchy
                  .filter(store => remainingStoresIds.includes(store.store_id as string))
                  .map(store => store.name)
                  .join(', ');

                  return (
                    <>
                      {visibleItems.map((item, idx) => {
                        const store = component?.data?.merchant_store_hierarchy?.store_hierarchy?.find(store => store.store_id === item);
                        return (
                          <Badge
                          key={idx}
                          color="neutral"
                          size="large"
                          marginLeft="spacing.3"
                          marginTop="spacing.2"
                          >
                            {store?.name ?? item}
                          </Badge> 
                        )
                      })}
                      {remainingStoresIds.length > 0 && (
                        <Tooltip content={remainingStoresNames ?? ''} placement="top">
                          <Badge
                            color="neutral"
                            size="large"
                            marginLeft="spacing.3"
                            marginTop="spacing.2"
                          >
                            +{remainingStoresIds.length} more
                          </Badge>
                        </Tooltip>
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
                marginTop="spacing.2"
              />
            )}
          </Box>
        );
      })}
    </>
  );
};
