import { render, screen, userEvent } from 'test-utils';
import { Provider } from 'react-redux';
import { storeWithInitialState } from 'merchant/store';

import CODPrepaidConfigs from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/containers/CODPrepaidConfigs';

import { VALIDATION_MSGS } from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/constants';
import {
  COD_PREPAID_CONFIGS_PROPS,
  DROPDOWN_INPUTS,
} from 'merchant/views/MagicCheckout/CODToPrepaid/ConfigsDashboard/__tests__/mocks/fixtures';

import * as ModalActions from 'merchant_common/reducers/modals';

jest.mock('common/ui/Forms/SwitchField', () => (props) => {
  const { checked, onChange } = props;
  return (
    <div>
      <p>{checked ? 'Checked' : 'Unchecked'}</p>
      <button type="button" onClick={onChange}>
        Toggle
      </button>
    </div>
  );
});

const renderApp = ({ state, ...props } = {}) => {
  render(
    <Provider store={storeWithInitialState({ ...state })}>
      <CODPrepaidConfigs {...props} />
    </Provider>,
  );
};

describe('testing the cod prepaid configs component', () => {
  const openModalSpy = jest.spyOn(ModalActions, 'openModal');

  test('should render properly', () => {
    const configs = {
      discount: {
        type: 'percentage',
        discount_percentage: 10,
        max_discount: 10000,
        minimum_order_value: 20000,
      },
      risk_category: ['high', 'medium', 'low'],
      communication: {
        expire_seconds: 1800,
        methods: ['whatsapp'],
      },
    };
    renderApp({ ...COD_PREPAID_CONFIGS_PROPS, prepayCODConfigs: { ...configs } });
    expect(screen.getByText(/Convert COD to Prepaid orders/i)).toBeInTheDocument();
  });

  test.each(DROPDOWN_INPUTS)(
    'should be able to change drop down and radio button values',
    async (item) => {
      renderApp({ ...COD_PREPAID_CONFIGS_PROPS });
      const { roleType, fieldValue, testId } = item;

      if (item.roleType === 'radio') {
        const radioElement = screen.getByRole(roleType, {
          name: fieldValue,
        });

        await userEvent.click(radioElement);
        expect(radioElement).toBeChecked();
      } else {
        const dropdownElement = screen.getByTestId(testId);
        await userEvent.selectOptions(dropdownElement, fieldValue);
        expect(screen.getByRole('option', { name: fieldValue }).selected).toBe(true);
      }
    },
  );

  test('should be able to save configuration', async () => {
    const configs = {
      discount: {
        type: 'flat',
        discount_percentage: 0,
        max_discount: 10000,
        minimum_order_value: 20000,
      },
      risk_category: ['high', 'medium'],
      communication: {
        expire_seconds: 3600,
        methods: ['whatsapp'],
      },
    };
    renderApp({ ...COD_PREPAID_CONFIGS_PROPS, prepayCODConfigs: { ...configs } });
    const saveCta = screen.getByRole('button', {
      name: 'Save settings',
    });

    await userEvent.click(saveCta);
    expect(openModalSpy).toHaveBeenCalled();
  });

  test('should show proper error messages when user input invalid custom time', async () => {
    const configs = {
      discount: {
        type: 'flat',
        discount_percentage: 0,
        max_discount: 10000,
        minimum_order_value: 20000,
      },
      risk_category: ['high'],
      communication: {
        expire_seconds: 173400,
        methods: ['whatsapp'],
      },
    };
    renderApp({ ...COD_PREPAID_CONFIGS_PROPS, prepayCODConfigs: { ...configs } });

    //if the time exceeds the max time
    expect(screen.getAllByText(VALIDATION_MSGS.duration.maxTimeError)).toHaveLength(2);

    const hourElement = screen.getByTestId('custom-hours');
    const minElement = screen.getByTestId('custom-mins');

    //should be able to use counter arrows to increase decrease time
    await userEvent.click(screen.getByTestId('custom-mins-arrow-down'));
    expect(minElement.value).toBe('9');

    //set value to 0 if no value is available in the input
    await userEvent.click(screen.getByTestId('custom-hours-arrow-up'));
    expect(hourElement.value).toBe('48');

    //should not be able to enter more than 48 hours
    await userEvent.click(screen.getByTestId('custom-hours-arrow-up'));
    expect(hourElement.value).toBe('48');
  });

  test('should show proper error messages when user input invalid discount', async () => {
    const configs = {
      discount: {
        type: 'flat',
        discount_percentage: 0,
        max_discount: 100,
        minimum_order_value: 200,
      },
      risk_category: [],
      communication: {
        expire_seconds: 173400,
        methods: ['whatsapp'],
      },
    };
    renderApp({ ...COD_PREPAID_CONFIGS_PROPS, prepayCODConfigs: { ...configs } });

    const minOrderValueElement = screen.getByTestId('minOrderValue');
    const maxDiscountElement = screen.getByTestId('maxDiscount');

    //should show the required error
    await userEvent.clear(minOrderValueElement);
    expect(screen.queryAllByText(VALIDATION_MSGS.required)).toHaveLength(1);

    //show discount error if minimum order value is less than max order amount
    await userEvent.clear(minOrderValueElement);
    await userEvent.clear(maxDiscountElement);

    await userEvent.type(minOrderValueElement, '200');
    await userEvent.type(maxDiscountElement, '300');
    expect(screen.queryAllByText(VALIDATION_MSGS.discount)).toHaveLength(1);

    //should not any error if the values are valid
    await userEvent.clear(maxDiscountElement);
    await userEvent.type(maxDiscountElement, '100');
    expect(screen.queryAllByText(VALIDATION_MSGS.discount)).toHaveLength(0);
  });

  test('should be able to enable discount', async () => {
    const configs = {
      discount: {
        type: 'zero',
        discount_percentage: 0,
        max_discount: 0,
        minimum_order_value: 0,
      },
      risk_category: [],
      communication: {
        expire_seconds: 173400,
        methods: ['whatsapp'],
      },
    };
    renderApp({
      ...COD_PREPAID_CONFIGS_PROPS,
      showPrepayCODToggle: false,
      prepayCODConfigs: { ...configs },
    });

    const discountToggle = screen.getByRole('button', {
      name: 'Toggle',
    });

    await userEvent.click(discountToggle);
    expect(screen.getByText(/enabled/i)).toBeInTheDocument();
  });
});
