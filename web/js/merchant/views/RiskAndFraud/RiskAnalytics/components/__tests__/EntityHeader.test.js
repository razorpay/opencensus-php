import React from 'react';

import { EntityHeader } from 'merchant/views/RiskAndFraud/RiskAnalytics/components';
import {
  FRAUD,
  DISPUTES,
  RISK_DECLINED,
  ENTITY_HEADER,
} from 'merchant/views/RiskAndFraud/RiskAnalytics/constants';
import { replaceBusinessName } from 'merchant/views/RiskAndFraud/common/utils';
import { render, screen, userEvent } from 'test-utils';

const entitiesToTest = [FRAUD, DISPUTES, RISK_DECLINED];

describe('EntityHeader', () => {
  entitiesToTest.forEach((entity) => {
    test(`renders EntityHeader component with content respective to ${entity} entity`, async () => {
      render(<EntityHeader entity={entity} />);
      const { popoverText, title, description, popoverTitle, popoverContent, imageAlt } =
        ENTITY_HEADER[entity];
      expect(screen.getByText(title)).toBeInTheDocument();
      expect(screen.getByText(description)).toBeInTheDocument();
      expect(screen.getByText(popoverText)).toBeInTheDocument();
      await userEvent.click(screen.getByText(popoverText));
      expect(screen.getByText(popoverTitle)).toBeInTheDocument();
      expect(screen.getByAltText(imageAlt)).toBeInTheDocument();
      const expectedPopoverContent = replaceBusinessName({
        str: popoverContent,
        businessName: 'razorpay',
        isCaps: true,
      });
      expect(screen.getByText(expectedPopoverContent)).toBeInTheDocument();
    });
  });
});
