import React from 'react';

import { EntityHeader } from 'merchant/views/RiskAndFraud/RiskAnalytics/components';
import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  ENTITY_HEADER,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { render, screen, userEvent } from 'test-utils';

const entitiesToTest = [FRAUD, DISPUTES, RISK_DECLINED];

describe('EntityHeader', () => {
  test.each(entitiesToTest)(
    'renders EntityHeader component with content respective to %s entity',
    async (entity) => {
      render(<EntityHeader entity={entity} />);
      const popoverText = screen.getByText(ENTITY_HEADER[entity].popoverText);
      expect(screen.getByText(ENTITY_HEADER[entity].title)).toBeInTheDocument();
      expect(screen.getByText(ENTITY_HEADER[entity].description)).toBeInTheDocument();
      expect(popoverText).toBeInTheDocument();
      await userEvent.click(popoverText);
      expect(screen.getByText(ENTITY_HEADER[entity].popoverTitle)).toBeInTheDocument();
      expect(screen.getByText(ENTITY_HEADER[entity].popoverContent)).toBeInTheDocument();
      expect(screen.getByAltText(ENTITY_HEADER[entity].imageAlt)).toBeInTheDocument();
    },
  );
});
