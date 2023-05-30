import React from 'react';
import { render, screen } from 'test-utils';
import EditorModal from 'merchant/views/PaymentButton/PaymentButton/Create/components/Form/components/EditorModal';

describe('EditorModal', () => {
  const renderApp = (props) => render(<EditorModal {...props} />);
  test('EditorModal to be defined', () => {
    expect(EditorModal).toBeDefined();
  });

  test('should render children element to be defined', () => {
    const children = <div>EditorModal</div>;
    const props = {
      children,
    };
    renderApp(props);
    expect(screen.getByText('EditorModal')).toBeInTheDocument();
  });
});
