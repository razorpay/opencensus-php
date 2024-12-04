import * as React from 'react';
import { screen, fireEvent } from '@testing-library/react';

import { CardButton } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/AdvancedCOD/components/CardButton';
import { lazyRenderComponent } from 'merchant/views/MagicCheckout/Settings/containers/MagicXCOD/test-helpers';

const props = {
  title: 'Title',
  description: 'Description',
  icon: <i />,
  onClick: jest.fn(),
};

const render = lazyRenderComponent(CardButton, props);

describe('CardButton', () => {
  test('should render', () => {
    render();
    expect(screen.getByText(/title/i)).toBeInTheDocument();
  });

  test('should call `onClick` function when clicked', () => {
    render();
    fireEvent.click(screen.getByRole('button'));
    expect(props.onClick).toHaveBeenCalled();
  });
});
