import List, {
  EmptyComponent as emptyComponent,
} from 'merchant/views/MagicCheckout/MagicIntelligence/components/List';
import { Provider } from 'react-redux';
import { render } from '@testing-library/react';
import { storeWithInitialState } from 'merchant/store';
import { Router } from 'react-router-dom';
import { createMemoryHistory } from 'history';
import { fireEvent, screen } from 'test-utils';

const initProps = {
  fetchAll: null,
  ctaText: 'allowlist',
  onUploadClick: jest.fn(),
  formName: 'list-form',
  list: ['phone', 'email'],
  attributeType: 'phone',
  setAttributeType: jest.fn(),
  attributeValue: '',
  setAttributeValue: jest.fn(),
  resetHandler: jest.fn(),
  count: 25,
  setCount: jest.fn(),
  skip: {
    current: 0,
  },
  hasNoData: {
    current: false,
  },
};

const AppWithRouter = ({ state = {}, ...props }) => {
  return (
    <Router history={createMemoryHistory({ initialEntries: ['/'] })}>
      <Provider
        store={storeWithInitialState({
          ...state,
        })}
      >
        <List {...initProps} {...props} />
      </Provider>
    </Router>
  );
};

describe('List filter component', () => {
  test('should show list filters inside the component', () => {
    render(<AppWithRouter />);
    ['Type', 'Value', 'Count'].forEach((fieldLabel) => {
      expect(screen.getByText(new RegExp(fieldLabel, 'i'))).toBeInTheDocument();
    });
  });

  test('should have input field in the component', () => {
    const { container } = render(<AppWithRouter />);
    ['value', 'count'].forEach((fieldInput) => {
      expect(container.querySelector(`input[name="${fieldInput}"]`)).toBeInTheDocument();
    });
  });

  test('should have select field inside the component', () => {
    const { container } = render(<AppWithRouter />);
    expect(container.querySelector(`select[name="type"]`)).toBeInTheDocument();
  });

  test('should call submit function on search CTA', () => {
    render(<AppWithRouter fetchAll={jest.fn()} />);
    const searchCTA = screen.getByRole('button', {
      name: 'Search',
    });
    expect(searchCTA).toBeInTheDocument();
    fireEvent.click(searchCTA);
  });

  test('should call reset function on clear CTA', () => {
    render(<AppWithRouter />);
    const clearCTA = screen.getByRole('button', {
      name: 'Clear',
    });
    expect(clearCTA).toBeInTheDocument();
    fireEvent.click(clearCTA);
  });

  test('should not show value filter if type selected is all', () => {
    render(<AppWithRouter attributeType="" />);
    expect(screen.queryByText(/Value/i)).not.toBeInTheDocument();
  });

  test('should be able to search if type is all', () => {
    render(<AppWithRouter attributeType="" fetchAll={jest.fn()} />);
    const searchCTA = screen.getByRole('button', {
      name: 'Search',
    });
    expect(searchCTA).toBeInTheDocument();
    fireEvent.click(searchCTA);
  });

  test('should show the selected type inside the input field', () => {
    const { container } = render(<AppWithRouter />);
    [
      {
        name: 'type',
        type: 'select',
        changed_value: 'phone',
        function: initProps.setAttributeType,
      },
      {
        name: 'value',
        type: 'input',
        changed_value: 'test@gmail.com',
        function: initProps.setAttributeValue,
      },
      {
        name: 'count',
        type: 'input',
        changed_value: 15,
        function: initProps.setCount,
      },
    ].forEach((field) => {
      const fieldElement = container.querySelector(`${field.type}[name="${field.name}"]`);
      expect(fieldElement).toBeInTheDocument();
      fireEvent.change(fieldElement, {
        target: {
          value: field.changed_value,
        },
      });
      expect(field.function).toHaveBeenCalled();
    });
  });
});

describe('Empty Component', () => {
  const hasNoData = {
    current: true,
  };
  const onUploadClick = jest.fn();
  const txt = 'item';

  test('should show no result found if clicking on search leads to empty data array', () => {
    const EmptyComponent = emptyComponent(onUploadClick, txt, hasNoData);
    render(<EmptyComponent />);
    expect(screen.getByText(/No result found!/i)).toBeInTheDocument();
  });

  test('should show CTA if initially not list is set', () => {
    hasNoData.current = false;
    const EmptyComponent = emptyComponent(onUploadClick, txt, hasNoData);
    render(<EmptyComponent />);
    expect(screen.queryByRole('button')).toBeInTheDocument();
  });
});
