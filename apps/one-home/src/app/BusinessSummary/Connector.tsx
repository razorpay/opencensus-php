import React, { useState, useEffect, RefObject } from 'react';
import { BoxRefType, useTheme } from '@razorpay/blade/components';

const ARROW_SIZE = 6;

interface ConnectorProps {
  startRef: RefObject<BoxRefType>;
  endRef: RefObject<BoxRefType>;
  parentRef: RefObject<BoxRefType>;
  direction: 'horizontal' | 'vertical';
  type: 'highToLow' | 'lowToHigh';
}

const Connector: React.FC<ConnectorProps> = ({ startRef, endRef, direction, type, parentRef }) => {
  const { theme } = useTheme();
  const [paths, setPaths] = useState({
    line1: '',
    line2: '',
    fillPath: '',
    gradient1: { x1: 0, y1: 0, x2: 0, y2: 0 },
    gradient2: { x1: 0, y1: 0, x2: 0, y2: 0 },
  });

  useEffect(() => {
    const updatePaths = () => {
      if (!startRef?.current || !endRef?.current || !parentRef?.current) {
        console.warn('Connector refs not available.');
        setPaths({
          line1: '',
          line2: '',
          fillPath: '',
          gradient1: { x1: 0, y1: 0, x2: 0, y2: 0 },
          gradient2: { x1: 0, y1: 0, x2: 0, y2: 0 },
        });
        return;
      }

      requestAnimationFrame(() => {
        const parentBox = parentRef?.current?.getBoundingClientRect();
        const startBox = startRef?.current?.getBoundingClientRect();
        const endBox = endRef?.current?.getBoundingClientRect();

        if (!startBox || !endBox || !parentBox) {
          console.warn('Bounding rects not available');
          return;
        }

        // Adjust positions to be relative to the parent
        const startXOffset = startBox.left - parentBox.left;
        const startYOffset = startBox.top - parentBox.top;
        const endXOffset = endBox.left - parentBox.left;
        const endYOffset = endBox.top - parentBox.top;

        const offset = 10; // Offset for spacing between lines
        let startX1, startY1, startX2, startY2, endX1, endY1, endX2, endY2, line1, line2, fillPath;

        if (direction === 'horizontal') {
          if (type === 'highToLow') {
            // High-to-Low: Converging lines
            startX1 = startXOffset + startBox.width;
            startY1 = startYOffset + offset;
            endX1 = endXOffset - ARROW_SIZE / 2;
            endY1 = endYOffset + endBox.height / 10;

            startX2 = startXOffset + startBox.width;
            startY2 = startYOffset + startBox.height - offset;
            endX2 = endXOffset - ARROW_SIZE / 2;
            endY2 = endYOffset + (9 * endBox.height) / 10;
          } else {
            // Low-to-High: Diverging lines
            startX1 = startXOffset + startBox.width;
            startY1 = startYOffset + offset;
            endX1 = endXOffset - ARROW_SIZE / 2;
            endY1 = endYOffset + offset;

            startX2 = startXOffset + startBox.width;
            startY2 = startYOffset + startBox.height - offset;
            endX2 = endXOffset - ARROW_SIZE / 2;
            endY2 = endYOffset + endBox.height - offset;
          }

          line1 = `M ${startX1} ${startY1} C ${(startX1 + endX1) / 2} ${startY1}, ${
            (startX1 + endX1) / 2
          } ${endY1}, ${endX1} ${endY1}`;
          line2 = `M ${startX2} ${startY2} C ${(startX2 + endX2) / 2} ${startY2}, ${
            (startX2 + endX2) / 2
          } ${endY2}, ${endX2} ${endY2}`;
          fillPath = `M ${startX1} ${startY1} C ${(startX1 + endXOffset) / 2} ${startY1}, ${
            (startX1 + endXOffset) / 2
          } ${endY1}, ${endXOffset} ${endY1} L ${endXOffset} ${endY2} C ${
            (startX2 + endXOffset) / 2
          } ${endY2}, ${(startX2 + endXOffset) / 2} ${startY2}, ${startX2} ${startY2} Z`;
        } else {
          if (type === 'highToLow') {
            // High-to-Low: Converging lines
            startX1 = startXOffset + startBox.width / 8;
            startY1 = startYOffset + startBox.height;
            endX1 = endXOffset + endBox.width / 10;
            endY1 = endYOffset - ARROW_SIZE / 2;

            startX2 = startXOffset + (7 * startBox.width) / 8;
            startY2 = startYOffset + startBox.height;
            endX2 = endXOffset + (9 * endBox.width) / 10;
            endY2 = endYOffset - ARROW_SIZE / 2;
          } else {
            // Low-to-High: Diverging lines
            startX1 = startXOffset + startBox.width / 4;
            startY1 = startYOffset + startBox.height;
            endX1 = endXOffset + endBox.width / 4;
            endY1 = endYOffset - ARROW_SIZE / 2;

            startX2 = startXOffset + (3 * startBox.width) / 4;
            startY2 = startYOffset + startBox.height;
            endX2 = endXOffset + (3 * endBox.width) / 4;
            endY2 = endYOffset - ARROW_SIZE / 2;
          }

          line1 = `M ${startX1} ${startY1} C ${startX1} ${(startY1 + endY1) / 2}, ${endX1} ${
            (startY1 + endY1) / 2
          }, ${endX1} ${endY1}`;
          line2 = `M ${startX2} ${startY2} C ${startX2} ${(startY2 + endY2) / 2}, ${endX2} ${
            (startY2 + endY2) / 2
          }, ${endX2} ${endY2}`;
          fillPath = `M ${startX1} ${startY1} 
                  C ${startX1} ${(startY1 + endYOffset) / 2}, ${endX1} ${
            (startY1 + endYOffset) / 2
          }, ${endX1} ${endYOffset}
                  L ${endX2} ${endYOffset} 
                  C ${endX2} ${(startY2 + endYOffset) / 2}, ${startX2} ${
            (startY2 + endYOffset) / 2
          }, ${startX2} ${startY2} 
                  Z`;
        }

        const gradient1 = { x1: startX1, y1: startY1, x2: endX1, y2: endY1 };
        const gradient2 = { x1: startX2, y1: startY2, x2: endX2, y2: endY2 };

        setPaths({ line1, line2, fillPath, gradient1, gradient2 });
      });
    };

    const handleResize = () => {
      updatePaths();
    };

    updatePaths();
    window.addEventListener('resize', handleResize);
    return () => window.removeEventListener('resize', handleResize);
  }, [startRef, endRef, direction, type, parentRef]);

  const gradient1 = `gradient-line1_${type}_${direction}`;
  const gradient2 = `gradient-line2_${type}_${direction}`;

  return (
    <svg
      style={{
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        pointerEvents: 'none',
      }}
    >
      <defs>
        <linearGradient
          id={gradient1}
          gradientUnits="userSpaceOnUse"
          x1={paths.gradient1.x1}
          y1={paths.gradient1.y1}
          x2={paths.gradient1.x2}
          y2={paths.gradient1.y2}
        >
          <stop
            offset="0%"
            stopColor={theme.colors.surface.background.primary.intense}
            stopOpacity="0.3"
          />
          <stop
            offset="50%"
            stopColor={theme.colors.surface.background.primary.intense}
            stopOpacity="1"
          />
        </linearGradient>

        <linearGradient
          id={gradient2}
          gradientUnits="userSpaceOnUse"
          x1={paths.gradient2.x1}
          y1={paths.gradient2.y1}
          x2={paths.gradient2.x2}
          y2={paths.gradient2.y2}
        >
          <stop
            offset="0%"
            stopColor={theme.colors.surface.background.primary.intense}
            stopOpacity="0.3"
          />
          <stop
            offset="50%"
            stopColor={theme.colors.surface.background.primary.intense}
            stopOpacity="1"
          />
        </linearGradient>

        <marker
          id={`arrow-${direction}`}
          markerWidth={ARROW_SIZE}
          markerHeight={ARROW_SIZE}
          refX={ARROW_SIZE / 2}
          refY={ARROW_SIZE / 2}
          orient={'auto'}
          markerUnits="strokeWidth"
        >
          <path
            d={`M 0 0 L ${ARROW_SIZE} ${ARROW_SIZE / 2} L 0 ${ARROW_SIZE} Z`}
            fill={theme.colors.surface.background.primary.intense}
          />
        </marker>
      </defs>

      {/* Fill Path */}
      <path d={paths.fillPath} fill={theme.colors.surface.background.primary.subtle} />

      {/* Line 1 */}
      <path
        d={paths.line1}
        stroke={`url(#${gradient1})`}
        strokeWidth="1.5"
        fill="none"
        strokeLinecap="round"
        markerEnd={`url(#arrow-${direction})`}
      />

      {/* Line 2 */}
      <path
        d={paths.line2}
        stroke={`url(#${gradient2})`}
        strokeWidth="1.5"
        fill="none"
        strokeLinecap="round"
        markerEnd={`url(#arrow-${direction})`}
      />
    </svg>
  );
};

export default Connector;
