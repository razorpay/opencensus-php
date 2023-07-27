import React from 'react';
import { screen, render, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';
import CategoryMapping from 'merchant/views/MagicCheckout/CODSettings/components/CODEngine/Configuration/Modal/CategoryMapping';

jest.mock(
  'merchant/views/MagicCheckout/MagicSettings/components/common/SettingsToggle',
  () => (props) => {
    const { onToggle } = props;
    return (
      <button data-testId="product-category-toggle" onClick={onToggle}>
        Toggle click
      </button>
    );
  },
);

const App = ({ state = {}, ...props }) => {
  return (
    <Provider store={storeWithInitialState({ ...state })}>
      <CategoryMapping {...props} />
    </Provider>
  );
};

describe('testing category mapping component', () => {
  test('should render properly', () => {
    render(<App />);
    expect(screen.getByText('Block COD')).toBeInTheDocument();
  });

  test('should be able to click on toggle', async () => {
    const customProps = {
      isCODBlocked: true,
      setIsCODBlocked: jest.fn(),
    };
    render(<App {...customProps} />);
    const toggleCta = screen.getByRole('button', {
      name: 'Toggle click',
    });
    await userEvent.click(toggleCta);
    expect(customProps.setIsCODBlocked).toHaveBeenCalled();
  });
});
