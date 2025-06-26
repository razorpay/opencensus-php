import React, { useState, useMemo, useCallback } from "react";
import { Box } from "@razorpay/blade/components";
import BentoCard from './BentoCard';
import { BentoCardData } from "./types";
import { getGridTemplateColumns, getRows, useDeviceType, getGridColumn } from "./utils";

interface BentoBoxProps {
  bentoCards: BentoCardData[];
  cardSetType?: string;
}

const BentoBox = ({ bentoCards, cardSetType = 'versionUpdates' }: BentoBoxProps): React.ReactElement => {
  const deviceType = useDeviceType();
  const [flipped, setFlipped] = useState<{ [key: string]: boolean }>({});

  const isMobile = deviceType === 'mobile';

  // prevent re-rendering of bentoCards. Pass bentoCards via API call (in future)
  const renderedCards = useMemo(() => {
    const result = getRows(deviceType, bentoCards);
    return result;
  }, [deviceType, bentoCards]);

  // Memoize parent handlers
  const handleFlip = useCallback((key: string) => {
    setFlipped((prev) => ({ ...prev, [key]: true }));
  }, []);
  const handleClose = useCallback((key: string) => {
    setFlipped((prev) => ({ ...prev, [key]: false }));
  }, []);

  return (
    <Box
      display="grid"
      gridTemplateColumns={getGridTemplateColumns(deviceType)}
      gap="spacing.6"
      testID="bento-box-grid"
    >
      {renderedCards.map((panel, idx) => {
        const gridColumn = getGridColumn(deviceType, panel.span, panel.spanFullTablet);
        const image = panel.boxType === 'large' ? panel.imgLarge : panel.imgSmall;
        // Memoize handlers per card
        const onFlip = useCallback(() => handleFlip(panel.key), [handleFlip, panel.key]);
        const onClose = useCallback(() => handleClose(panel.key), [handleClose, panel.key]);
        return (
          <Box
            key={panel.key}
            gridColumn={gridColumn}
            backgroundColor="transparent"
            borderRadius="medium"
            display="flex"
          >
            <BentoCard
              {...panel}
              image={image}
              flipped={!!flipped[panel.key]}
              onFlip={onFlip}
              onClose={onClose}
              isMobile={isMobile}
              cardSetType={cardSetType}
            />
          </Box>
        );
      })}
    </Box>
  );
};

export default BentoBox;
