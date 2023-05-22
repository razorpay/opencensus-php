import UserInfo from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/UserInfo';
import { PersonalProfileFields } from 'merchant/views/AccountAndSettings/AccountAndSettingsHome/typings';
import React from 'react';
import { render, screen, userEvent } from 'test-utils';

jest.mock(
  'merchant/views/AccountAndSettings/AccountAndSettingsHome/sections/Profile/views/v2/components/Verification',
  () => () => {
    return (
      <div>
        <span>Verification v2 Module</span>
      </div>
    );
  },
);

const defaultProps = {
  isMobile: false,
  infoData: [
    {
      id: PersonalProfileFields.NAME,
      displayName: 'Name',
      value: 'John Doe',
      isEditEnable: true,
      selfServeActionName: 'UserName Updated',
      analyticsEventInfo: {
        objectName: 'user name edit',
        actionName: 'clicked',
      },
    },
    {
      id: PersonalProfileFields.EMAIL,
      displayName: 'Email',
      value: 'john@example.com',
      isEditEnable: false,
      editTooltip: {
        description: 'Email cannot be edited.',
      },
      selfServeActionName: 'Email Updated',
      analyticsEventInfo: {
        objectName: 'email edit',
        actionName: 'clicked',
      },
    },
  ],
};

describe('UserInfo', () => {
  const onEditClick = jest.fn();

  const renderApp = (props: Record<string, any>) =>
    render(<UserInfo onClick={onEditClick} {...defaultProps} {...props} />);

  test.each(defaultProps.infoData)('should render info display name and value', (infoData) => {
    renderApp({});
    expect(screen.getByText(infoData.displayName)).toBeInTheDocument();
    expect(screen.getByText(infoData.value)).toBeInTheDocument();
  });

  test('onEditClick should be invoked on edit click', async () => {
    renderApp({
      isMobile: true,
    });
    const editButton = screen.getAllByText('Edit');
    await userEvent.click(editButton[0]);
    expect(onEditClick).toHaveBeenCalled();
  });
});
